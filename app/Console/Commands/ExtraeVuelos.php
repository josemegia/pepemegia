<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GmailService;
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
                            {--meses=36 : Meses hacia atrás para buscar correos} 
                            {--dry-run : Muestra los datos sin guardarlos}
                            {--from= : Filtra por dirección o dominio del remitente}
                            {--subject= : Filtra por texto contenido en el asunto}';

    protected $description = 'Extrae reservas de vuelos desde Gmail y completa las tablas pasajeros y reservas';

    protected $geminiApiKey;
    protected $geminiModelName;

    public function __construct()
    {
        parent::__construct();
        $this->geminiApiKey = config('services.gemini.key');
        $this->geminiModelName = config('services.gemini.model');
    }

    public function handle(): int
    {
        //$meses = (int) $this->option('meses');
        $archivoFecha = Storage::disk('local')->path('ultima_ejecucion_vuelos.txt');
        $desdeFecha = now()->subMonths((int) $this->option('meses', 36)); // fallback

        if (file_exists($archivoFecha)) {
            $contenido = trim(file_get_contents($archivoFecha));
            try {
                $fechaRegistrada = \Carbon\Carbon::parse($contenido);
                if ($fechaRegistrada->isValid()) {
                    $desdeFecha = $fechaRegistrada->copy()->subDay(); // seguridad: restar 1 día
                    $this->info("📅 Última ejecución registrada: {$fechaRegistrada->toDateString()}");
                }
            } catch (\Exception $e) {
                $this->warn("⚠️ Fecha de última ejecución inválida, se usará fallback.");
            }
        }
        $emails = $this->option('email') 
            ? [trim($this->option('email'))] 
            : config('reservas.accounts', []);

        $filterFrom = strtolower($this->option('from') ?? '');
        $filterSubject = strtolower($this->option('subject') ?? '');

        if (empty($emails)) {
            $this->error('❌ No hay cuentas configuradas para extracción.');
            return Command::FAILURE;
        }

        foreach ($emails as $email) {
            $this->info("📬 Procesando cuenta: {$email}");
            $gmail = new GmailService($email);
            //$mensajes = $gmail->getReservationEmails($meses, 'airline');
            $mensajes = $gmail->getReservationEmailsDesde($desdeFecha, 'airline');


            $this->info("📩 Correos encontrados: " . count($mensajes));

            foreach ($mensajes as $mensaje) {
                $this->info("──────────────✉️ Procesando mensaje {$mensaje['id']} ──────────────");

                $remitente = $this->extraerEmailReal($mensaje['content']['from'] ?? '');
                $asunto = $mensaje['content']['subject'] ?? '';
                $body = $mensaje['content']['body'] ?? '';
                $pdfs = $mensaje['content']['adjuntos_pdf_texto'] ?? [];

                Log::debug("📧 Remitente: {$remitente}");
                Log::debug("📝 Asunto: {$asunto}");
                Log::debug("📎 PDFs adjuntos: " . count($pdfs));

                if ($filterFrom && !str_contains(strtolower($remitente), $filterFrom)) {
                    $this->line("⏭️  Omitido por filtro --from: {$remitente}");
                    continue;
                }

                if ($filterSubject && !str_contains(strtolower($asunto), $filterSubject)) {
                    $this->line("⏭️  Omitido por filtro --subject: {$asunto}");
                    continue;
                }

                $parsedData = app(AirlineDetectorService::class)->parseFromMensaje($mensaje);

                if (!empty($parsedData)) {
                    $this->info("✅ Datos extraídos desde PDF.");
                    Log::debug("📄 Resultado del parser PDF:", $parsedData);
                } elseif (!empty($body)) {
                    $contenidoLimpio = strip_tags($body);
                    $longitudTexto = strlen(trim($contenidoLimpio));

                    if (!$this->option('no-gemini') && $longitudTexto > 1000) {
                        $this->info("🤖 Usando Gemini como fallback (texto: {$longitudTexto} caracteres)");
                        $prompt = $this->construirPromptParaGemini($contenidoLimpio);
                        $respuesta = $this->callGeminiApiViaHttp($prompt);

                        if ($this->esJsonValido($respuesta)) {
                            $parsedData = json_decode($respuesta, true);
                            Log::debug("🤖 Gemini devolvió:", $parsedData);
                        } else {
                            $this->warn("⚠️ Gemini devolvió JSON inválido");
                            Log::warning("⚠️ Gemini sin JSON válido: " . $respuesta);
                        }
                    } else {
                        Log::info("🔁 Gemini evitado: texto insuficiente ({$longitudTexto}) o --no-gemini activado.");
                    }
                }

                if (!$parsedData) {
                    $this->warn("⚠️ No se pudieron extraer datos del mensaje {$mensaje['id']}");
                    continue;
                }

                if ($this->option('dry-run')) {
                    $this->info("🧪 DRY-RUN: Datos extraídos, no se guardará nada.");
                    dump($parsedData);
                    continue;
                }

                DB::beginTransaction();
                try {
                    $pd = $parsedData['pasajero_data'] ?? null;
                    $pasajero = null;

                    if (!empty($pd['nombre_unificado'])) {
                        $nombreUnificado = $pd['nombre_unificado'];
                        $nombreOriginal = $pd['nombre_original'] ?? null;

                        $pasajero = Pasajero::where('nombre_unificado', $nombreUnificado)
                            ->orWhereJsonContains('variantes', $nombreOriginal)
                            ->first();

                        if ($pasajero) {
                            Log::info("🔄 Pasajero encontrado (por nombre_unificado o variante):", $pasajero->toArray());

                            // Añadir variante si aún no está
                            if ($nombreOriginal && !in_array($nombreOriginal, $pasajero->variantes ?? [])) {
                                $variantes = $pasajero->variantes ?? [];
                                $variantes[] = $nombreOriginal;
                                $pasajero->variantes = array_values(array_unique($variantes));
                                $pasajero->save();
                                Log::info("🧩 Variante añadida a pasajero existente: {$nombreOriginal}");
                            }
                        } else {
                            $pasajero = Pasajero::create([
                                'nombre_unificado' => $nombreUnificado,
                                'nombre_original' => $nombreOriginal,
                                'variantes' => [],
                            ]);
                            Log::info("🆕 Pasajero creado:", $pasajero->toArray());
                        }

                    } else {
                        $this->warn("⚠️ No se puede crear pasajero: falta nombre_unificado");
                        Log::warning("❌ No se creó pasajero: falta nombre_unificado", ['mensaje_id' => $mensaje['id']]);
                        DB::rollBack();
                        continue;
                    }

                    $reservaBase = $parsedData['reserva_data'] ?? $parsedData;
                    if (isset($reservaBase['tipo'])) {
                        $reservaBase['tipo_reserva'] = $reservaBase['tipo'];
                        unset($reservaBase['tipo']);
                    }

                    $segmentos = $reservaBase['datos_adicionales']['segmentos_vuelo'] ?? [];
                    Log::debug("🧩 Segmentos a guardar:", $segmentos);

                    $reservas = app(ReservationRegistrar::class)->guardar(
                        $reservaBase,
                        $segmentos,
                        $pasajero,
                        $mensaje['email_origen'],
                        $mensaje['id'],
                        $mensaje['content']['body'] ?? null
                    );

                    if ($reservas->isEmpty()) {
                        $this->warn("⚠️ No se guardaron reservas (posible duplicado)");
                        Log::warning("❗ Duplicados detectados, no se insertó", [
                            'pasajero_id' => $pasajero->id,
                            'mensaje_id' => $mensaje['id'],
                        ]);
                    } else {
                        Log::info("📦 Se guardaron {$reservas->count()} reservas", [
                            'pasajero_id' => $pasajero->id,
                            'mensaje_id' => $mensaje['id'],
                        ]);
                        $this->info("✅ Reservas guardadas correctamente.");
                    }

                    DB::commit();

                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error("❌ Error al guardar reservas", [
                        'mensaje_id' => $mensaje['id'] ?? null,
                        'email_origen' => $mensaje['email_origen'] ?? null,
                        'exception' => $e->getMessage(),
                        'trace' => Str::limit($e->getTraceAsString(), 1000),
                    ]);
                    $this->error("❌ Excepción: {$e->getMessage()}");
                }

            }
        }
        
        if (!$this->option('dry-run')) {
            Storage::disk('local')->put('ultima_ejecucion_vuelos.txt', now()->toDateTimeString());
            $this->info("🗓️  Fecha de ejecución guardada: " . now()->toIso8601String());
        } else {
            $this->warn("🧪 DRY-RUN: no se actualiza fecha de ejecución.");
        }

        return Command::SUCCESS;
    }

    private function callGeminiApiViaHttp(string $prompt): ?string
    {
        if (empty($this->geminiApiKey)) {
            $this->error('❌ GEMINI_API_KEY no está configurado.');
            return null;
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->geminiModelName}:generateContent?key={$this->geminiApiKey}";

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
            $this->error("❌ Error de Gemini API ({$response->status()}): " . mb_substr($response->body(), 0, 300));
            Log::error("❌ Gemini API error: " . $response->body());
            return null;
        }

        return $response->json('candidates.0.content.parts.0.text');
    }

    private function construirPromptParaGemini(string $body): string
    {
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
        if (preg_match('/<(.+?)>/', $fromHeader, $coincidencias)) {
            return strtolower($coincidencias[1]);
        }
        return strtolower(trim($fromHeader));
    }

    private function esJsonValido(?string $str): bool
    {
        if (empty($str)) return false;
        json_decode($str);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
