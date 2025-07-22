<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Models\RecaptchaBlockedIp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Carbon;

use Google\Cloud\RecaptchaEnterprise\V1\Client\RecaptchaEnterpriseServiceClient;
use Google\Cloud\RecaptchaEnterprise\V1\Event;
use Google\Cloud\RecaptchaEnterprise\V1\Assessment;
use Google\Cloud\RecaptchaEnterprise\V1\CreateAssessmentRequest;
use Google\Cloud\RecaptchaEnterprise\V1\TokenProperties\InvalidReason;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    protected $projectId;
    protected $siteKey;
    protected $credentialsPath;
    protected $minRecaptchaScore;
    protected $maxRecaptchaAttempts;
    protected $blockDurationDays; // Nueva propiedad para la duración del bloqueo

    public function __construct()
    {
        $this->projectId = config('services.recaptcha.enterprise.project_id');
        $this->siteKey = config('services.recaptcha.enterprise.site_key');
        $this->credentialsPath = config('services.recaptcha.enterprise.credentials_path');
        $this->minRecaptchaScore = config('services.recaptcha.enterprise.min_score', 0.7);
        $this->maxRecaptchaAttempts = config('services.recaptcha.enterprise.max_attempts', 5);
        // Define la duración del bloqueo en días, por defecto 30 días
        $this->blockDurationDays = config('services.recaptcha.enterprise.block_duration_days', 30);
    }

    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => $this->passwordRules(),
            'g-recaptcha-response' => ['required', 'string'],
        ])->validate();

        $userIp = Request::ip();
        $recaptchaToken = $input['g-recaptcha-response'];

        if (!preg_match('/^[A-Za-z0-9\-_]+$/', $recaptchaToken)) {
            Log::warning("reCAPTCHA: Token con formato inválido detectado para IP: {$userIp}");
            $this->handleRecaptchaFailure($userIp, 'El token de seguridad tiene un formato inválido. Por favor, inténtalo de nuevo.');
        }

        if (!file_exists($this->credentialsPath)) {
            Log::error('reCAPTCHA Enterprise: Credentials file not found. Path: ' . $this->credentialsPath);
            $this->handleRecaptchaFailure($userIp, 'Error de configuración de seguridad en el servidor: Credenciales no encontradas.');
        }

        try {
            $client = new RecaptchaEnterpriseServiceClient([
                'credentials' => $this->credentialsPath,
            ]);

            $projectName = $client->projectName($this->projectId);

            $event = (new Event())
                ->setSiteKey($this->siteKey)
                ->setToken($recaptchaToken)
                ->setUserIpAddress($userIp);

            $assessment = (new Assessment())
                ->setEvent($event);

            $request = (new CreateAssessmentRequest())
                ->setParent($projectName)
                ->setAssessment($assessment);

            $response = $client->createAssessment($request);
            $client->close();

            Log::info('reCAPTCHA Enterprise API Response:', [
                'tokenProperties' => [
                    'valid' => $response->getTokenProperties()->getValid(),
                    'invalidReason' => $response->getTokenProperties()->getInvalidReason() !== null ? InvalidReason::name($response->getTokenProperties()->getInvalidReason()) : 'N/A',
                    'action' => $response->getTokenProperties()->getAction(),
                    'createTime' => $response->getTokenProperties()->getCreateTime()
                                        ? (new \DateTime())->setTimestamp($response->getTokenProperties()->getCreateTime()->getSeconds())->format('Y-m-d H:i:s')
                                        : 'N/A',
                    'hostname' => $response->getTokenProperties()->getHostname() ?? 'N/A',
                ],
                'riskAnalysis' => [
                    'score' => $response->getRiskAnalysis()->getScore(),
                    'reasons' => array_map(function ($reason) {
                        return \Google\Cloud\RecaptchaEnterprise\V1\RiskAnalysis\Reason::name($reason);
                    }, iterator_to_array($response->getRiskAnalysis()->getReasons())),
                ],
                'accountDefenderAssessment' => $response->hasAccountDefenderAssessment() ? $response->getAccountDefenderAssessment()->serializeToJsonString() : 'N/A',
                'fraudPreventionAssessment' => $response->hasFraudPreventionAssessment() ? $response->getFraudPreventionAssessment()->serializeToJsonString() : 'N/A',
            ]);

            if ($response->getTokenProperties()->getValid() === false) {
                $reason = $response->getTokenProperties()->getInvalidReason() !== null ? InvalidReason::name($response->getTokenProperties()->getInvalidReason()) : 'UNKNOWN';
                Log::warning('reCAPTCHA Enterprise token invalid: ' . $reason . ' for IP: ' . $userIp);
                $this->handleRecaptchaFailure($userIp, 'La verificación de seguridad ha fallado: Token inválido. Por favor, inténtalo de nuevo.');
            }

            if ($response->getTokenProperties()->getAction() !== 'register') {
                Log::warning('reCAPTCHA Enterprise action mismatch.', [
                    'expected' => 'register',
                    'received' => $response->getTokenProperties()->getAction(),
                    'ip' => $userIp,
                ]);
                $this->handleRecaptchaFailure($userIp, 'La acción de seguridad no coincide. Por favor, inténtalo de nuevo.');
            }

            if ($response->getRiskAnalysis()->getScore() < $this->minRecaptchaScore) {
                Log::warning('reCAPTCHA Enterprise score too low for IP: ' . $userIp, [
                    'score' => $response->getRiskAnalysis()->getScore(),
                    'reasons' => array_map(function ($reason) {
                        return \Google\Cloud\RecaptchaEnterprise\V1\RiskAnalysis\Reason::name($reason);
                    }, iterator_to_array($response->getRiskAnalysis()->getReasons())),
                ]);
                $this->handleRecaptchaFailure($userIp, 'La verificación de seguridad ha detectado actividad sospechosa. Inténtalo más tarde.');
            }

            // Si todas las verificaciones de reCAPTCHA pasan, elimina cualquier registro de intentos fallidos
            // para esta IP. Esto "desbloquea" explícitamente una IP si logra pasar la verificación.
            RecaptchaBlockedIp::where('ip', $userIp)->delete();

        } catch (\Google\ApiCore\ApiException $e) {
            Log::error('reCAPTCHA Enterprise API call failed (Google API Error): ' . $e->getMessage(), ['code' => $e->getCode(), 'trace' => $e->getTraceAsString()]);
            $this->handleRecaptchaFailure($userIp, 'Error de comunicación con el servicio de seguridad. Por favor, inténtalo de nuevo o contacta al soporte.');
        } catch (\Exception $e) {
            Log::error('Unexpected error during reCAPTCHA Enterprise verification: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $this->handleRecaptchaFailure($userIp, 'Error inesperado de seguridad. Por favor, inténtalo de nuevo.');
        }

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            'role' => 'user',
        ]);
    }

    /**
     * Handles reCAPTCHA verification failure, logs it, updates/creates blocked IP record,
     * and throws a ValidationException. It also cleans up old blocked IPs.
     *
     * @param string $ip The IP address to handle.
     * @param string $message The error message for the user.
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function handleRecaptchaFailure(string $ip, string $message): void
    {
        // --- INICIO: Lógica para desbloquear IPs antiguas en el mismo momento del bloqueo ---
        $this->cleanOldBlockedIps();
        // --- FIN: Lógica para desbloquear IPs antiguas ---

        $blockedIp = RecaptchaBlockedIp::firstOrNew(['ip' => $ip]);
        $blockedIp->attempts++;
        $blockedIp->last_attempt_at = Carbon::now();

        if ($blockedIp->wasRecentlyCreated || ($blockedIp->attempts === 1 || $blockedIp->attempts === $this->maxRecaptchaAttempts)) {
            try {
                $metadataResponse = Http::timeout(3)->get("https://ipinfo.io/{$ip}/json");
                if ($metadataResponse->successful()) {
                    $blockedIp->metadata = $metadataResponse->json();
                } else {
                    Log::warning("No se pudo obtener metadata de ipinfo.io para IP {$ip}. Estado: " . $metadataResponse->status());
                    $blockedIp->metadata = ['error' => 'No se pudo obtener metadata de ipinfo.io.'];
                }
            } catch (\Exception $ex) {
                Log::error("Error al obtener metadata de ipinfo.io para IP {$ip}: " . $ex->getMessage());
                $blockedIp->metadata = ['error' => 'Excepción al obtener metadata de ipinfo.io.'];
            }
        }

        if ($blockedIp->attempts >= $this->maxRecaptchaAttempts && !$blockedIp->blocked_at) {
            $blockedIp->blocked_at = Carbon::now();
            Log::critical("IP {$ip} ha sido bloqueada debido a intentos excesivos de reCAPTCHA.");
            throw ValidationException::withMessages([
                'g-recaptcha-response' => 'Demasiados intentos fallidos. Tu IP ha sido bloqueada temporalmente. Por favor, contacta al soporte si crees que es un error.',
            ]);
        }

        $blockedIp->save();

        throw ValidationException::withMessages([
            'g-recaptcha-response' => $message,
        ]);
    }

    /**
     * Cleans up RecaptchaBlockedIp records that are older than the configured block duration.
     */
    protected function cleanOldBlockedIps(): void
    {
        $thresholdDate = Carbon::now()->subDays($this->blockDurationDays);

        $unblockedCount = RecaptchaBlockedIp::whereNotNull('blocked_at')
                                            ->where('blocked_at', '<', $thresholdDate)
                                            ->delete();

        if ($unblockedCount > 0) {
            Log::info("reCAPTCHA IP Cleaner: Se han desbloqueado {$unblockedCount} IPs antiguas durante el proceso de bloqueo/intento.");
        }
    }
}