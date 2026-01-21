<?php //app Console Commands ExtraVuelos.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GmailService;
use App\Models\User;
use App\Services\ReservationExtractor;
use App\Services\ReservationRegistrar;
use App\Services\AirlineDetectorService;
use App\Models\Reserva;
use App\Models\Pasajero;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExtraeVuelos extends Command
{
    protected $signature = 'vuelos:extraer 
                            {--no-gemini : No utilizar Gemini como fallback}
                            {--email= : Cuenta específica de Gmail a procesar} 
                            {--meses= : Meses hacia atrás para buscar correos} 
                            {--dry-run : Muestra los datos sin guardarlos}
                            {--from= : Filtra por dirección o dominio del remitente}
                            {--subject= : Filtra por texto contenido en el asunto}
                            {--fecha-inicio= : Filtra correos desde una fecha específica (YYYY-MM-DD)}
                            {--fecha-fin= : Filtra correos hasta una fecha específica (YYYY-MM-DD)}';

    protected $description = 'Extrae reservas de vuelos desde las cuentas de Gmail de los usuarios administradores';

    protected $geminiApiKey;
    protected $geminiModelName;
    protected $geminiApiUrl;

    public function __construct()
    {
        parent::__construct();
        $this->geminiApiKey = config('services.gemini.key');
        $this->geminiModelName = config('services.gemini.model');
        $this->geminiApiUrl = config('services.gemini.url');
    }

    public function handle(): int
    {
        $this->info("\033[32m🚀 Iniciando el comando 'vuelos:extraer'. Preparando configuración inicial...\033[0m");

        $desdeFecha = null;
        $hastaFecha = null;

        // ✅ NUEVO: contador global para decidir si se actualiza ultima_ejecucion_vuelos.txt
        $totalMensajesEncontrados = 0;

        // --- BLOQUE DE CÓDIGO DEFINITIVO ---
        // Prioridad 1: Usar fechas específicas si se proporciona --fecha-inicio O --fecha-fin
        if ($this->option('fecha-inicio') || $this->option('fecha-fin')) {
            try {
                // Define la fecha de inicio
                if ($this->option('fecha-inicio')) {
                    $desdeFecha = \Carbon\Carbon::parse($this->option('fecha-inicio'))->startOfDay();
                } else {
                    // Si no hay inicio, usa el fallback de 36 meses como punto de partida
                    $desdeFecha = now()->subMonths(36)->startOfDay();
                    $this->info("\033[33m⚠️ No se proveyó --fecha-inicio. Usando fallback de 36 meses como inicio.\033[0m");
                }

                // Define la fecha de fin
                if ($this->option('fecha-fin')) {
                    $hastaFecha = \Carbon\Carbon::parse($this->option('fecha-fin'))->endOfDay();
                } else {
                    // Si no hay fin, se asume que es hasta hoy
                    $hastaFecha = now()->endOfDay();
                }

                $this->info("\033[32m📅 Usando rango de fechas: Desde {$desdeFecha->toDateString()} hasta {$hastaFecha->toDateString()}\033[0m");

            } catch (\Exception $e) {
                $this->error('Alguna de las fechas proporcionadas no es válida. Usa el formato YYYY-MM-DD.');
                return 1;
            }

        } else {
            $archivoFecha = Storage::disk('local')->path('ultima_ejecucion_vuelos.txt');
            $this->info("\033[36m📂 Verificando archivo de última ejecución: {$archivoFecha}\033[0m");

            $mesesOpt = $this->option('meses');                 // string|null
            $meses    = is_null($mesesOpt) ? 0 : (int) $mesesOpt; // 0 si NO se pasó explícitamente

            if (!is_null($mesesOpt) && $meses > 0) {

                $desdeFecha = now()->subMonths($meses)->startOfDay();
                $hastaFecha = now()->endOfDay();

                $this->info("\033[32m📅 Opción --meses activada explícitamente. Extrayendo desde hace {$meses} meses: {$desdeFecha->toDateString()}\033[0m");

            } elseif (file_exists($archivoFecha)) {

                $this->info("\033[36m📄 Archivo de última ejecución encontrado. Leyendo contenido...\033[0m");

                try {
                    $contenido = trim(file_get_contents($archivoFecha));
                    $fechaRegistrada = \Carbon\Carbon::parse($contenido);

                    $desdeFecha = $fechaRegistrada->copy()->subDay()->startOfDay(); // margen de seguridad
                    $hastaFecha = now()->endOfDay();

                    $this->info("\033[32m📅 Usando última ejecución registrada: {$fechaRegistrada->toDateString()} → desde {$desdeFecha->toDateString()}\033[0m");

                } catch (\Exception $e) {

                    $desdeFecha = now()->subMonths(36)->startOfDay();
                    $hastaFecha = now()->endOfDay();

                    $this->warn("\033[33m⚠️ Fecha inválida en archivo. Usando fallback de 36 meses: {$desdeFecha->toDateString()}\033[0m");
                }

            } else {

                $desdeFecha = now()->subMonths(36)->startOfDay();
                $hastaFecha = now()->endOfDay();

                $this->info("\033[33m📅 No hay archivo previo. Usando fallback de 36 meses: {$desdeFecha->toDateString()}\033[0m");
            }

        }
        
        $usersToProcess = [];
        if ($email = $this->option('email')) {
            $this->info("\033[32m📧 Opción --email activada. Procesando cuenta específica: {$email}\033[0m"); // Green for options
            $user = User::where('email', trim($email))->first();
            if ($user) {
                $usersToProcess[] = $user;
                $this->info("\033[32m✅ Usuario encontrado para {$email}\033[0m"); // Green for success
            } else {
                $this->error("\033[31m❌ Usuario no encontrado para {$email}\033[0m"); // Red for error
            }
        } else {
            $this->info("\033[36m🔍 Buscando todos los usuarios administradores con tokens de Google...\033[0m"); // Cyan for search
            $usersToProcess = User::where('role', 'admin')
                                  ->whereNotNull('social_provider_refresh_token')
                                  ->get();
            $this->info("\033[35m📊 Encontrados " . count($usersToProcess) . " usuarios administradores válidos\033[0m"); // Magenta for counts/data
        }

        if (empty($usersToProcess) || (is_countable($usersToProcess) && count($usersToProcess) === 0)) {
            $this->error("\033[31m❌ No se encontraron usuarios válidos para procesar. Finalizando comando.\033[0m"); // Red for failure
            return Command::FAILURE;
        }

        $filterFrom = strtolower($this->option('from') ?? '');
        $filterSubject = strtolower($this->option('subject') ?? '');
        if ($filterFrom) {
            $this->info("\033[32m🔍 Filtro --from activado: {$filterFrom}\033[0m"); // Green for filters
        }
        if ($filterSubject) {
            $this->info("\033[32m🔍 Filtro --subject activado: {$filterSubject}\033[0m"); // Green for filters
        }

        foreach ($usersToProcess as $user) {
            $this->info("\033[36m📬 Iniciando procesamiento para la cuenta del admin: {$user->email}\033[0m"); // Cyan for processing start
            
            try {
                $this->info("\033[36m🔌 Creando instancia de GmailService para el usuario...\033[0m");
                $gmail = new GmailService($user);

                $this->info("\033[36m📥 Obteniendo correos de reservas desde {$desdeFecha->toDateString()}...\033[0m");
                $mensajes = $gmail->getReservationEmailsDesde($desdeFecha, $hastaFecha, 'airline');

                $this->info("\033[35m📩 Total de correos encontrados: " . count($mensajes) . "\033[0m");

                // ✅ acumular para decidir si se actualiza el archivo de ultima ejecución
                $totalMensajesEncontrados += count($mensajes);

                foreach ($mensajes as $mensaje) {
                    $this->info("\033[36m──────────────✉️ Iniciando procesamiento del mensaje ID: {$mensaje['id']} ──────────────\033[0m");

                    $remitente = $this->extraerEmailReal($mensaje['content']['from'] ?? '');
                    $asunto = $mensaje['content']['subject'] ?? '';
                    $body = $mensaje['content']['body'] ?? '';
                    $pdfs = $mensaje['content']['adjuntos_pdf_texto'] ?? [];

                    $this->info("\033[35m📧 Remitente extraído: {$remitente}\033[0m");
                    $this->info("\033[35m📝 Asunto del mensaje: {$asunto}\033[0m");
                    $this->info("\033[35m📎 Número de PDFs adjuntos: " . count($pdfs) . "\033[0m");

                    if ($filterFrom && !str_contains(strtolower($remitente), $filterFrom)) {
                        $this->line("\033[33m⏭️ Mensaje omitido por filtro --from: No coincide con {$remitente}\033[0m");
                        continue;
                    }

                    if ($filterSubject && !str_contains(strtolower($asunto), $filterSubject)) {
                        $this->line("\033[33m⏭️ Mensaje omitido por filtro --subject: No coincide con {$asunto}\033[0m");
                        continue;
                    }

                    $this->info("\033[36m🔍 Intentando parsear datos desde el mensaje usando AirlineDetectorService...\033[0m");
                    $parsedData = app(AirlineDetectorService::class)->parseFromMensaje($mensaje);

                    // ✅ Si no hay nada que guardar, pasamos al siguiente email
                    if (empty($parsedData) || !is_array($parsedData)) {
                        $this->warn("\033[33m⚠️ No se pudo extraer una reserva válida de este mensaje.\033[0m");
                        continue;
                    }

                    // ✅ Normaliza: el detector puede devolver ['items' => [...]] o un único item
                    $parsedList = (isset($parsedData['items']) && is_array($parsedData['items']))
                        ? $parsedData['items']
                        : [$parsedData];


                    foreach ($parsedList as $parsed) {

                        // ✅ NUEVO: validar estructura mínima
                        if (
                            !is_array($parsed) ||
                            empty($parsed['reserva_data']) ||
                            empty($parsed['pasajero_data']) ||
                            !is_array($parsed['reserva_data']) ||
                            !is_array($parsed['pasajero_data'])
                        ) {
                            $this->warn("\033[33m⚠️ Item inválido (sin reserva_data/pasajero_data). Saltando...\033[0m");
                            continue;
                        }

                        if ($this->option('dry-run')) {
                            $this->info("\033[33m🧪 Modo DRY-RUN activado: Mostrando datos extraídos sin guardar.\033[0m");
                            dump($parsed);
                            continue;
                        }

                        $this->info("\033[36m💾 Iniciando transacción de base de datos para guardar datos...\033[0m");
                        DB::beginTransaction();

                        try {
                            $pd = $parsed['pasajero_data'] ?? null;
                            $pasajero = null;

                            if (empty($pd['nombre_unificado'])) {
                                $this->warn("\033[33m⚠️ Falta nombre_unificado. nombre_original=" . ($pd['nombre_original'] ?? 'NULL') . "\033[0m");
                                DB::rollBack();
                                continue;
                            }

                            $this->info("\033[35m👤 Datos de pasajero detectados. Nombre unificado: {$pd['nombre_unificado']}\033[0m");
                            $nombreUnificado = $pd['nombre_unificado'];
                            $nombreOriginal = $pd['nombre_original'] ?? null;

                            $pasajero = Pasajero::where('nombre_unificado', $nombreUnificado)
                                ->orWhereJsonContains('variantes', $nombreOriginal)
                                ->first();

                            if ($pasajero) {
                                $this->info("\033[32m🔄 Pasajero encontrado (ID: {$pasajero->id}).\033[0m");
                                if ($nombreOriginal && !in_array($nombreOriginal, $pasajero->variantes ?? [])) {
                                    $variantes = $pasajero->variantes ?? [];
                                    $variantes[] = $nombreOriginal;
                                    $pasajero->variantes = array_values(array_unique($variantes));
                                    $pasajero->save();
                                    $this->info("\033[32m🧩 Variante añadida: {$nombreOriginal}\033[0m");
                                }
                            } else {
                                $this->info("\033[32m🆕 Creando nuevo pasajero: {$nombreUnificado}\033[0m");
                                $pasajero = Pasajero::create([
                                    'nombre_unificado' => $nombreUnificado,
                                    'nombre_original' => $nombreOriginal,
                                    'variantes' => [],
                                ]);
                            }

                            $reservaBase = $parsed['reserva_data'] ?? $parsed;
                            if (isset($reservaBase['tipo'])) {
                                $reservaBase['tipo_reserva'] = $reservaBase['tipo'];
                                unset($reservaBase['tipo']);
                            }

                            $segmentos = $reservaBase['datos_adicionales']['segmentos_vuelo'] ?? [];
                            $this->info("\033[35m🧩 Segmentos: " . count($segmentos) . "\033[0m");

                            $reservas = app(ReservationRegistrar::class)->guardar(
                                $reservaBase,
                                $segmentos,
                                $pasajero,
                                $mensaje['email_origen'],
                                $mensaje['id'],
                                $mensaje['content']['body'] ?? null
                            );

                            if ($reservas->isEmpty()) {
                                $this->warn("\033[33m⚠️ Reserva duplicada detectada.\033[0m");
                            } else {
                                $this->info("\033[32m✅ Se guardaron {$reservas->count()} reservas.\033[0m");
                            }

                            DB::commit();
                            $this->info("\033[32m✅ Transacción confirmada.\033[0m");

                        } catch (\Exception $e) {
                            $this->error("\033[31m❌ Error al guardar reservas. Revirtiendo...\033[0m");
                            DB::rollBack();
                            Log::error("❌ Error al guardar reservas", [
                                'mensaje_id' => $mensaje['id'] ?? null,
                                'email_origen' => $mensaje['email_origen'] ?? null,
                                'exception' => $e->getMessage(),
                                'trace' => Str::limit($e->getTraceAsString(), 1000),
                            ]);
                        }
                    }
                
                }
            }
            catch (\Google\Service\Exception $e) {
                // ✅ Manejo claro de OAuth roto (invalid_grant / token revocado)
                $raw = $e->getMessage();
                $decoded = json_decode($raw, true);

                $isInvalidGrant =
                    str_contains($raw, 'invalid_grant')
                    || (is_array($decoded) && (($decoded['error'] ?? null) === 'invalid_grant'))
                    || (is_array($decoded) && (($decoded['error_description'] ?? null) === 'Token has been expired or revoked.'))
                    || (is_array($decoded) && (($decoded['error']['status'] ?? null) === 'UNAUTHENTICATED'));

                if ($isInvalidGrant) {
                    $this->error("\033[31m❌ {$user->email}: Google OAuth inválido (token revocado/expirado). Reautoriza esta cuenta.\033[0m");
                    Log::error("❌ OAuth invalid_grant para {$user->email}. Reautoriza esta cuenta.", [
                        'email' => $user->email,
                        'code' => $e->getCode(),
                        'raw' => $raw,
                    ]);
                    // IMPORTANTÍSIMO: no seguimos con esta cuenta, pasamos al siguiente usuario
                    continue;
                }

                $this->error("\033[31m❌ Error Google API para {$user->email} ({$e->getCode()}): {$raw}\033[0m");
                Log::error("❌ Error Google API para {$user->email}", [
                    'email' => $user->email,
                    'code' => $e->getCode(),
                    'raw' => $raw,
                ]);

                // En otros 401/403, también saltamos de cuenta para no spamear
                if ($e->getCode() == 401 || $e->getCode() == 403) {
                    continue;
                }

                // otros errores Google: seguimos con siguiente cuenta igualmente
                continue;
            }
            catch (\Exception $e) {
                $this->error("\033[31m❌ Error crítico al procesar la cuenta {$user->email}: {$e->getMessage()}\033[0m");
                Log::error("❌ Error crítico al procesar cuenta", [
                    'email' => $user->email,
                    'exception' => $e->getMessage(),
                    'trace' => Str::limit($e->getTraceAsString(), 2000),
                ]);
                continue;
            }

        }

        // ✅ CORRECCIÓN: solo actualizar si NO es dry-run y se encontró al menos 1 correo
        if (!$this->option('dry-run') && $totalMensajesEncontrados > 0) {
            $this->info("\033[36m🗓️ Actualizando archivo de última ejecución con fecha actual: " . now()->toDateTimeString() . "\033[0m"); // Cyan for update
            Storage::disk('local')->put('ultima_ejecucion_vuelos.txt', now()->toDateTimeString());
            $this->info("\033[32m🗓️ Fecha de ejecución guardada exitosamente.\033[0m"); // Green for success
        } else {
            $this->warn("\033[33m🧪 No se actualiza ultima_ejecucion_vuelos.txt (dry-run o 0 correos encontrados).\033[0m");
        }

        $this->info("\033[32m🏁 Comando finalizado con éxito.\033[0m"); // Green for end
        return Command::SUCCESS;
    }

    private function callGeminiApiViaHttp(string $prompt): ?string
    {
        $this->info("\033[36m🔑 Verificando clave API de Gemini...\033[0m"); // Cyan for check
        if (empty($this->geminiApiKey)) {
            $this->error("\033[31m❌ GEMINI_API_KEY no está configurado. No se puede llamar a la API.\033[0m"); // Red for error
            return null;
        }

        $url = $this->geminiApiUrl . '?key=' . $this->geminiApiKey;
        $this->info("\033[36m📡 Preparando llamada HTTP a Gemini API en: {$url}\033[0m"); // Cyan for prep

        $response = Http::timeout(120)->retry(2, 1000)->withHeaders([
            'Content-Type' => 'application/json',
        ])->post($url, [
            'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
            'generationConfig' => [
                'temperature' => 0.2,
                'topK' => 30,
                'topP' => 0.90,
                'maxOutputTokens' => 4096,
            ],
        ]);

        if ($response->failed()) {
            $this->error("\033[31m❌ Error en llamada a Gemini API (Status: {$response->status()}): " . mb_substr($response->body(), 0, 300) . "\033[0m"); // Red for failure
            Log::error("❌ Gemini API error: " . $response->body());
            return null;
        }

        $this->info("\033[32m✅ Respuesta de Gemini recibida exitosamente.\033[0m"); // Green for success
        return $response->json('candidates.0.content.parts.0.text');
    }

    private function construirPromptParaGemini(string $body): string
    {
        $this->info("\033[36m🛠️ Construyendo prompt para Gemini con instrucciones y texto del email...\033[0m"); // Cyan for building
        $instrucciones = "Analiza el siguiente email relacionado con una reserva aérea. Extrae los datos en JSON con este formato: 
{
  \"tipo\": \"vuelo\",
  \"aerolinea\": \"Nombre de la aerolínea\",
  \"numero_vuelo\": \"ABC123\",
  \"fecha_salida\": \"YYYY-MM-DD\",
  \"fecha_llegada\": \"YYYY-MM-DD\",
  \"ciudad_destino\": \"Ciudad\",
  \"pais_destino\": \"País\",
  \"precio_total\": 123.45,
  \"moneda\": \"EUR\",
  \"numero_confirmacion\": \"XXXXXX\",
  \"nombre_pasajero_principal\": \"Nombre completo\"
}
Responde únicamente con el JSON. No expliques nada.";

        return "{$instrucciones}\n\nTexto del email:\n{$body}";
    }

    private function extraerEmailReal(string $fromHeader): string
    {
        $this->info("\033[36m📧 Extrayendo email real del header 'From': {$fromHeader}\033[0m"); // Cyan for extraction
        if (preg_match('/<(.+?)>/', $fromHeader, $coincidencias)) {
            $email = strtolower($coincidencias[1]);
            $this->info("\033[32m✅ Email extraído de ángulos: {$email}\033[0m"); // Green for success
            return $email;
        }
        $email = strtolower(trim($fromHeader));
        $this->info("\033[32m✅ Email extraído directamente: {$email}\033[0m"); // Green for success
        return $email;
    }

    private function esJsonValido(?string $str): bool
    {
        $this->info("\033[36m🧐 Validando si la respuesta es JSON válido...\033[0m"); // Cyan for validation
        if (empty($str)) {
            $this->info("\033[33m❌ Cadena vacía, no es JSON válido.\033[0m"); // Yellow for invalid
            return false;
        }
        json_decode($str);
        $esValido = json_last_error() === JSON_ERROR_NONE;
        if ($esValido) {
            $this->info("\033[32m✅ Es JSON válido.\033[0m"); // Green for valid
        } else {
            $this->info("\033[33m❌ No es JSON válido (Error: " . json_last_error_msg() . ")\033[0m"); // Yellow for invalid
        }
        return $esValido;
    }
}
