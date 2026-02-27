<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIAdvisorService
{
    private string $apiKey;
    private string $model;
    private string $endpoint;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key');
        $this->model = config('services.gemini.model', 'gemini-2.5-flash');
        $this->endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent";
    }

    public function analyzeMessage(string $message, string $geoCountryCode, array $availableCountries, array $files = []): array
    {
        $countriesList = collect($availableCountries)->map(fn($name, $code) => "{$code} => {$name}")->implode("\n");

        $prompt = <<<PROMPT
Eres un asistente que analiza mensajes de salud. Tu tarea es extraer:
1. El país donde la persona COMPRARÁ los productos (no de dónde es originario)
2. Las condiciones de salud mencionadas

Si se adjuntan imágenes o PDFs (análisis de sangre, recetas médicas, informes), ANALÍZALOS y extrae las condiciones de salud relevantes.

PAÍSES DISPONIBLES (código => nombre):
{$countriesList}

PAÍS DETECTADO POR GEOLOCALIZACIÓN: {$geoCountryCode}

REGLAS PARA EL PAÍS:
- Si el mensaje menciona un país donde la persona ESTÁ o VIVE, usa ese país
- Si el mensaje dice "para alguien en [país]", usa ese país
- Si el mensaje dice "soy de [país] pero vivo en [otro]", usa donde VIVE
- Si no menciona ningún país, usa el detectado por geolocalización: {$geoCountryCode}
- El código de país DEBE ser uno de la lista proporcionada

REGLAS PARA CONDICIONES:
- Extrae las condiciones de salud como términos generales en español
- Incluye síntomas, enfermedades, problemas mencionados
- Si hay análisis de sangre, identifica valores fuera de rango y las condiciones asociadas
- Usa términos simples: "insomnio", "dolor de cabeza", "tiroides", "fatiga", etc.

Responde SOLO con JSON válido, sin markdown ni explicaciones:
{"country_code": "código", "conditions": ["condición1", "condición2"]}

MENSAJE DEL USUARIO: {$message}
PROMPT;

        $response = $this->callApi($prompt, 0.1, 300, $files);

        if (!$response) {
            return [
                'country_code' => $geoCountryCode,
                'conditions' => [],
            ];
        }

        $clean = preg_replace('/```json\s*|\s*```/', '', trim($response));
        $parsed = json_decode($clean, true);

        if (!$parsed || !isset($parsed['country_code'])) {
            Log::warning('AIAdvisor: No se pudo parsear análisis', ['response' => $response]);
            return [
                'country_code' => $geoCountryCode,
                'conditions' => [],
            ];
        }

        if (!array_key_exists($parsed['country_code'], $availableCountries)) {
            $parsed['country_code'] = $geoCountryCode;
        }

        return $parsed;
    }

    public function generateProtocol(string $message, array $catalog, array $files = []): ?array
    {
        $countryInfo = $catalog['country'];
        $productsJson = json_encode($catalog['products'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $prompt = <<<PROMPT
Eres un asesor experto en suplementos 4Life con formación en inmunología y nutrición.
Tu tarea es generar un PROTOCOLO DE SUPLEMENTACIÓN personalizado basado en la consulta del usuario.

Si se adjuntan imágenes o PDFs (análisis de sangre, recetas, informes), ANALÍZALOS en detalle e incorpora los hallazgos al protocolo.

PAÍS DEL CLIENTE: {$countryInfo['name']} ({$countryInfo['code']})
MONEDA: {$countryInfo['currency']}
TIENDA: {$countryInfo['shop_url']}

CATÁLOGO DE PRODUCTOS DISPONIBLES (SOLO puedes recomendar estos):
{$productsJson}

REGLAS CRÍTICAS — VIOLACIÓN = ERROR GRAVE:
1. NUNCA inventes productos que no estén en el catálogo anterior
2. NUNCA cambies la presentación: si dice "cápsulas", di "cápsulas", NUNCA "sobres"
3. NUNCA recomiendes productos con is_available = false
4. Usa SIEMPRE el nombre exacto del producto del catálogo
5. Las dosis deben respetar el serving_size y dosage del catálogo
6. Si un producto no tiene precio (null), indícalo como "consultar precio"

FORMATO DE RESPUESTA (texto plano para WhatsApp, usa emojis):

🧬 *PROTOCOLO DE SUPLEMENTACIÓN 4LIFE*
📋 Consulta: [resumen breve de lo que pidió el usuario]
🌍 País: [país] | 💰 Moneda: [moneda]
━━━━━━━━━━━━━━━━━━━━

☀️ *MAÑANA (en ayunas o con desayuno):*
- [Producto] — [dosis exacta] — [formato correcto]

🌤️ *MEDIODÍA (con almuerzo):*
- [Producto] — [dosis exacta] — [formato correcto]

🌙 *NOCHE (con cena o antes de dormir):*
- [Producto] — [dosis exacta] — [formato correcto]

━━━━━━━━━━━━━━━━━━━━
🔬 *EXPLICACIÓN CIENTÍFICA:*
[Para CADA producto recomendado: nombre, ingredientes clave, mecanismo de acción y por qué ayuda en esta condición. Explica la sinergia entre productos si aplica.]

━━━━━━━━━━━━━━━━━━━━
🥗 *HÁBITOS COMPLEMENTARIOS:*
[3-5 recomendaciones de estilo de vida relevantes]

━━━━━━━━━━━━━━━━━━━━
💰 *RECOMENDACIÓN DE COMPRA MENSUAL:*
[Lista productos con precio unitario y total mensual]
[Si hay packs más baratos, recomiéndalos]
[Total estimado en moneda local]

━━━━━━━━━━━━━━━━━━━━
⚠️ *AVISO LEGAL:*
Este protocolo es orientativo y no sustituye el consejo médico profesional. Los suplementos alimenticios no están destinados a diagnosticar, tratar, curar ni prevenir ninguna enfermedad. Consulta con tu médico antes de iniciar cualquier suplementación.

🛒 Compra aquí: {$countryInfo['shop_url']}

CONSULTA DEL USUARIO: {$message}
PROMPT;

        $response = $this->callApi($prompt, 0.3, 8000, $files);

        if (!$response) {
            return null;
        }

        return [
            'formatted' => $this->formatForHtml($response),
            'raw' => $response,
        ];
    }

    private function callApi(string $prompt, float $temperature, int $maxTokens, array $files = []): ?string
    {
        try {
            // Construir las partes del contenido
            $parts = [];

            // Añadir archivos como inline_data
            foreach ($files as $file) {
                $mimeType = $file['type'];
                // Gemini soporta imágenes y PDFs
                if (str_starts_with($mimeType, 'image/') || $mimeType === 'application/pdf') {
                    $parts[] = [
                        'inline_data' => [
                            'mime_type' => $mimeType,
                            'data' => $file['base64'],
                        ],
                    ];
                }
            }

            // Añadir el texto
            $parts[] = ['text' => $prompt];

            $response = Http::timeout(90)
                ->post($this->endpoint . '?key=' . $this->apiKey, [
                    'contents' => [
                        [
                            'parts' => $parts,
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => $temperature,
                        'maxOutputTokens' => $maxTokens,
                    ],
                ]);

            if ($response->failed()) {
                Log::error('AIAdvisor: Gemini API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            return $response->json('candidates.0.content.parts.0.text');

        } catch (\Exception $e) {
            Log::error('AIAdvisor: Exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function formatForHtml(string $text): string
    {
        $html = e($text);
        $html = preg_replace('/\*([^*]+)\*/', '<strong>$1</strong>', $html);
        $html = nl2br($html);
        return $html;
    }
}
