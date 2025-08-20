<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

use Gemini\Laravel\Facades\Gemini;

use App\Mail\ZoomCreatedMail;

use Carbon\Carbon;

use Throwable;

class ZoomMeetingService
{
    public function createFromFlyerJson(string $jsonUrl): ?string
    {
        $data = $this->loadJson($jsonUrl);
        if (!$data) {
            throw new \Exception('No se pudo leer el archivo JSON desde ' . $jsonUrl);
        }

        $originalLink = $data['cta']['link'] ?? '';

        // Filtro rápido: si ni siquiera contiene /j/, no seguimos
        if (!str_contains(urldecode($originalLink), '/j/')) {
            return $originalLink;
        }

        // Extraer shortlink exacto (plano o codificado)
        $shortLinkToReplace = $this->extractShortLink($originalLink);
        if (!$shortLinkToReplace) {
            return $originalLink;
        }
        
        // ✨ Infiere la zona horaria usando Gemini AI
        $timezone = $this->inferTimezone($data);

        // Crear reunión Zoom
        $zoom = $this->createZoomMeeting([
            'topic' => $data['mainTitle'] ?? 'Evento 4Life',
            'agenda' => $data['subtitle'] ?? '',
            'start_time' => Carbon::parse(
                "{$data['event']['date']} {$data['event']['time']}",
                $timezone // Usa la zona horaria deducida
            )->setTimezone('UTC')->toIso8601String(),
            'timezone' => $timezone, // La pasa a Zoom también
            'password' => $this->extractPassword($data['event']['platform_details'] ?? ''),
        ]);

        $data['guest']['link'] = $zoom['join_url'] ?? '#';

        // Reemplazo doble: versión directa y codificada
        $replaced = str_replace(
            [rawurlencode($shortLinkToReplace), $shortLinkToReplace],
            [rawurlencode($data['guest']['link']), $data['guest']['link']],
            $originalLink
        );
        
        $data['event']['platform'] .= ' ' . $zoom['id'];
        $data['cta']['link'] = $replaced;
        $data['admin']['link'] = $zoom['start_url'];

        $this->saveJson($jsonUrl, $data);
        
        if (isset($data['email'])){
            Mail::to($data['email'])->send(new ZoomCreatedMail($data));
        }


        return $data['cta']['link'];
    }

    /**
     * Infiere la zona horaria usando Gemini AI a partir del contexto del evento.
     *
     * @param array $eventData Los datos completos del JSON del flyer.
     * @return string La zona horaria en formato IANA (ej. 'America/Bogota').
     */
    private function inferTimezone(array $eventData): string
    {
        // 1. Construir un texto de contexto para la IA
        $context = implode("\n", [
            'Título del evento: ' . ($eventData['mainTitle'] ?? ''),
            'Subtítulo: ' . ($eventData['subtitle'] ?? ''),
            'Plataforma: ' . ($eventData['event']['platform'] ?? ''),
            'Detalles: ' . ($eventData['event']['platform_details'] ?? ''),
            'PhonePrefix: ' . ($eventData['event']['phone_country'] ?? '')
        ]);

        // 2. Crear el prompt para Gemini
        $prompt = <<<PROMPT
Basado en el siguiente texto de un evento, identifica la zona horaria más probable.
Responde únicamente con el identificador de zona horaria estándar de PHP (formato IANA), por ejemplo: 'America/Bogota', 'Europe/Madrid', 'America/Mexico_City'.
Si no puedes determinar la zona horaria con certeza, responde con 'UTC'.

Texto del evento:
---
{$context}
---
PROMPT;

        try {
            // 3. Llamar a la API de Gemini
            $result = Gemini::gemini('gemini-pro')->generateContent($prompt);
            $timezone = trim($result->text());

            // 4. Validar que la respuesta sea una zona horaria válida
            if (in_array($timezone, timezone_identifiers_list())) {
                Log::info("Gemini infirió la zona horaria: {$timezone}");
                return $timezone;
            }
        } catch (Throwable $e) {
            // Si la API falla, registramos el error y usamos el valor por defecto
            Log::error('Error al inferir la zona horaria con Gemini: ' . $e->getMessage());
        }

        // 5. Si todo falla, usar la zona horaria por defecto de la app
        Log::warning("No se pudo inferir la zona horaria con Gemini. Usando fallback: " . config('app.timezone', 'UTC'));
        return config('app.timezone', 'UTC');
    }
    
    // --- MÉTODOS AUXILIARES (sin cambios) ---

    private function extractShortLink(string $text): ?string
    {
        $decoded = urldecode($text);
        if (preg_match('#https?://[^/]+/j/[a-zA-Z0-9]+#', $decoded, $match)) {
            return $match[0];
        }
        return null;
    }

    public function createZoomMeeting(array $params): array
    {
        $accessToken = $this->getZoomAccessToken();
        $payload = [
            'topic' => $params['topic'] ?? 'Evento',
            'type' => 2,
            'start_time' => $params['start_time'],
            'duration' => 60,
            'timezone' => $params['timezone'] ?? 'UTC',
            'password' => $params['password'] ?? Str::upper(Str::random(6)),
            'agenda' => $params['agenda'] ?? '',
            'settings' => [
                'join_before_host' => true,
                'approval_type' => 0,
                'waiting_room' => false,
                'auto_recording' => 'cloud',
            ]
        ];
        $response = Http::withToken($accessToken)->post('https://api.zoom.us/v2/users/me/meetings', $payload);
        if ($response->failed()) {
            throw new \Exception('Error al crear la reunión en Zoom: ' . $response->body());
        }
        return $response->json();
    }

    private function getZoomAccessToken(): string
    {
        $clientId = config('services.zoom.client_id');
        $clientSecret = config('services.zoom.client_secret');
        $accountId = config('services.zoom.account_id');
        $res = Http::withBasicAuth($clientId, $clientSecret)
            ->asForm()
            ->post('https://zoom.us/oauth/token', [
                'grant_type' => 'account_credentials',
                'account_id' => $accountId,
            ]);
        return $res->json('access_token');
    }

    private function extractPassword(string $text): string
    {
        if (preg_match('/(Clave|Contraseña)[:：]?\s*([A-Za-z0-9]+)/i', $text, $m)) {
            return $m[2];
        }
        return Str::upper(Str::random(6));
    }

    private function loadJson(string $url): ?array
    {
        if (preg_match('#/flyer/view/([a-z0-9\-]+)/([^/]+\.json)$#i', $url, $m)) {
            $uuid = $m[1];
            $filename = $m[2];
            $path = "flyers/shared/{$uuid}/{$filename}";
            
            if (Storage::disk('public')->exists($path)) {
                return json_decode(Storage::disk('public')->get($path), true);
            }
        }
        if (Str::contains($url, '/storage/')) {
            $path = Str::after($url, '/storage/');
            if (Storage::disk('public')->exists($path)) {
                return json_decode(Storage::disk('public')->get($path), true);
            }
        } elseif (Str::contains($url, '/flyer/view/')) {
            try {
                $response = Http::get($url);
                return $response->json();
            } catch (Throwable $e) {
                return null;
            }
        }
        return null;
    }

    private function saveJson(string $url, array $data): void
    {
        if (Str::contains($url, '/storage/')) {
            $path = Str::after($url, '/storage/');
            Storage::disk('public')->put(
                $path,
                json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
            );
        } else {
            Log::warning("No se guardó JSON porque la URL no es del storage local: $url");
        }
    }
}


