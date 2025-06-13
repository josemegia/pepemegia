<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Google\Service\Oauth2;
use Google\Service\Gmail;

class GoogleAuthController extends Controller
{
    public function redirectToGoogle(Request $request)
    {
        $client = $this->getClient();
        $authUrl = $client->createAuthUrl();

        Log::info('🔗 Redirigiendo a Google OAuth: ' . $authUrl);
        return redirect($authUrl);
    }

    public function handleGoogleCallback(Request $request)
    {
        try {
            $client = $this->getClient();

            if ($request->has('code')) {
                Log::info('🔁 Código recibido: ' . $request->input('code'));

                $token = $client->fetchAccessTokenWithAuthCode($request->input('code'));
                Log::debug('📦 Token recibido: ' . json_encode($token));

                if (isset($token['error'])) {
                    Log::error('❌ Error al obtener token de Google: ' . json_encode($token));
                    return response()->json(['error' => 'Error al obtener token de Google', 'details' => $token['error_description'] ?? json_encode($token)], 500);
                }

                if (!isset($token['access_token'])) {
                    Log::error('❌ Token inválido o vacío recibido de Google: ' . json_encode($token));
                    return response()->json(['error' => 'Token de acceso inválido o no encontrado en la respuesta'], 500);
                }

                // **LÍNEA CORREGIDA/ELIMINADA ABAJO**
                // En lugar de: $grantedScopes = $client->getGrantedScopes();
                // Obtenemos los scopes desde el array $token
                if (isset($token['scope'])) {
                    Log::info('Scopes concedidos según el token: ' . $token['scope']);
                }

                $client->setAccessToken($token); // Establece el token completo en el cliente

                $oauth2Service = new Oauth2($client);
                $userInfo = $oauth2Service->userinfo->get();
                $email = $userInfo->getEmail();

                if (!$email) {
                    Log::error('❌ No se pudo obtener el email del usuario. userInfo: ' . json_encode($userInfo));
                    // También es útil loguear si el id_token tiene el email, como fallback o para info
                    if (isset($token['id_token'])) {
                        $idTokenParts = explode('.', $token['id_token']);
                        if (count($idTokenParts) === 3) {
                            $payload = json_decode(base64_decode(strtr($idTokenParts[1], '-_', '+/')), true);
                            Log::info('ID Token payload: ' . json_encode($payload));
                            if (isset($payload['email'])) {
                                Log::info('Email del ID Token: ' . $payload['email']);
                                // $email = $payload['email']; // Podrías usar este si $userInfo->getEmail() falla
                            }
                        }
                    }
                    return response()->json(['error' => 'No se pudo obtener el email del usuario desde Google'], 500);
                }
                
                Log::info("📧 Email obtenido: {$email}");

                // --- Bloque para guardar el token y ajustar permisos ---
                $filename = "token-{$email}.json"; 
                // Dado que tu disco 'local' (por defecto) tiene 'root' => storage_path('app/private'),
                // $filename se guardará directamente en 'storage/app/private/token-EMAIL.json'
                
                $successWrite = Storage::disk('local')->put($filename, json_encode($token)); // Especificar disco 'local' es más explícito

                if ($successWrite) {
                    Log::info("✅ Token para {$email} guardado como {$filename} en el disco 'local'.");
                    
                    try {
                        // Storage::path() devuelve la ruta absoluta para el disco local
                        $absolutePath = Storage::disk('local')->path($filename); 
                        if (file_exists($absolutePath)) {
                            chmod($absolutePath, 0660); // rw-rw----
                            Log::info("Permisos del token {$absolutePath} ajustados a 0664.");
                        } else {
                            Log::warning("El archivo token {$absolutePath} no se encontró después de Storage::put para ajustar permisos.");
                        }
                    } catch (\Exception $e) {
                        Log::error("No se pudieron ajustar los permisos del token {$filename} (disco 'local'): " . $e->getMessage());
                    }
                } else {
                    Log::error("❌ FALLO al guardar el token para {$email} usando Storage::put('{$filename}') en disco 'local'.");
                    return response()->json(['error' => 'No se pudo guardar el archivo token'], 500);
                }
                // --- Fin del bloque para guardar token ---

                return response()->json(['success' => true, 'email' => $email, 'message' => 'Autenticación exitosa y token guardado.']);
            }

            Log::warning('⚠️ No se proporcionó código en el callback de Google.');
            return response()->json(['error' => 'Código de autorización no proporcionado en el callback'], 400);

        } catch (\Google\Service\Exception $e) {
            $errorDetails = json_decode($e->getMessage(), true);
            Log::error('❌ Excepción de Google Service en GoogleAuthController: ' . $e->getMessage(), [
                'code' => $e->getCode(),
                'errors' => $e->getErrors()
            ]);
            return response()->json([
                'error' => 'Excepción de API de Google',
                'details' => $errorDetails['error']['message'] ?? $e->getMessage(),
                'google_errors' => $e->getErrors()
            ], $e->getCode() ?: 500);
        } catch (\Exception $e) {
            Log::error('❌ Excepción general en GoogleAuthController: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Excepción del servidor: ' . $e->getMessage()], 500);
        }
    }

    // En GoogleAuthController.php
    private function getClient()
    {
        $client = new \Google\Client(); // Usar \Google\Client si no tienes un 'use Google\Client as GoogleClient;'
        $credentialsPath = Storage::disk('local')->path('credentials.json');

        if (!file_exists($credentialsPath)) {
            Log::critical('❌ Archivo credentials.json no encontrado en: ' . $credentialsPath);
            throw new \Exception('Archivo de credenciales de Google no configurado.');
        }
        $client->setAuthConfig($credentialsPath);

        // ----- ASEGÚRATE DE QUE ESTOS SCOPES ESTÉN AQUÍ -----
        $client->addScope(\Google\Service\Gmail::GMAIL_READONLY);     // Para leer emails
        $client->addScope(\Google\Service\Oauth2::USERINFO_EMAIL);  // Para obtener el email del usuario
        $client->addScope(\Google\Service\Oauth2::OPENID);          // Para OpenID Connect (a menudo se incluye con userinfo.email)
        // ----------------------------------------------------

        $client->setAccessType('offline'); // Para obtener refresh_token
        $client->setPrompt('consent');     // Importante para asegurar que el usuario vea la pantalla de consentimiento
                                        // y se le pidan todos los scopes de nuevo, especialmente si han cambiado.

        $redirectUri = route('google.callback');
        $client->setRedirectUri($redirectUri);

        return $client;
    }
}