<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\FlCountry;
use App\Models\FlConsultation;
use App\Services\CatalogService;
use App\Services\AIAdvisorService;

class FlAdvisorController extends Controller
{
    public function index(Request $request)
    {
        $countryCode = $this->detectCountryCode($request);

        $country = FlCountry::where('code', $countryCode)
            ->where('is_active', true)
            ->first()
            ?? FlCountry::where('code', 'usspanish')->first();

        $affiliateCode = $request->cookie('fl_affiliate_code');
        $affiliateName = $request->cookie('fl_affiliate_name');

        return view('fourlife.chat', [
            'country' => $country,
            'affiliateCode' => $affiliateCode,
            'affiliateName' => $affiliateName,
        ]);
    }

    /**
     * Valida y guarda el código de afiliado.
     * Intenta extraer el nombre de 4life.com.
     * Si Cloudflare bloquea, guarda el código igualmente.
     */
    public function saveCode(Request $request)
    {
        $request->validate([
            'code' => 'nullable|string|max:50',
        ]);

        $code = trim($request->input('code', ''));

        // Si el código está vacío, borrar cookies
        if (empty($code)) {
            return response()->json([
                'success' => true,
                'message' => 'Código eliminado.',
            ])->cookie(cookie()->forget('fl_affiliate_code'))
              ->cookie(cookie()->forget('fl_affiliate_name'));
        }

        // Validar formato básico
        if (!preg_match('/^[a-zA-Z0-9._-]+$/', $code)) {
            return response()->json([
                'success' => false,
                'message' => 'El formato del código no es válido.',
            ]);
        }

        // Intentar extraer nombre desde 4life.com (puede fallar por Cloudflare)
        $name = $this->fetchDistributorName($code);

        // Guardar cookies por 1 año
        $cookieCode = cookie('fl_affiliate_code', $code, 60 * 24 * 365);

        $res = response()->json([
            'success' => true,
            'code' => $code,
            'name' => $name,
            'message' => $name
                ? "¡Código verificado! Distribuidor: {$name}"
                : '¡Código guardado! Asegúrate de que el código sea correcto.',
        ])->cookie($cookieCode);

        if ($name) {
            $res = $res->cookie(cookie('fl_affiliate_name', $name, 60 * 24 * 365));
        }

        return $res;
    }

    public function consult(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $startTime = microtime(true);
        $message = $request->input('message');
        $files = $request->input('files', []);

        // 1. País base por GeoIP
        $geoCountryCode = $this->detectCountryCode($request);

        // 2. Lista de países disponibles
        $availableCountries = FlCountry::where('is_active', true)
            ->pluck('name', 'code')
            ->toArray();

        // 3. IA analiza mensaje: detecta país real + condiciones
        $ai = new AIAdvisorService();
        $analysis = $ai->analyzeMessage($message, $geoCountryCode, $availableCountries, $files);

        $countryCode = $analysis['country_code'];
        $conditions = $analysis['conditions'] ?? [];

        $country = FlCountry::where('code', $countryCode)
            ->where('is_active', true)
            ->first()
            ?? FlCountry::where('code', 'usspanish')->first();

        // 4. Buscar catálogo relevante
        $catalog = (new CatalogService())->getRelevantProducts($conditions, $country->code);

        // 5. Si no hay productos, informar
        if (empty($catalog['products'])) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontraron productos disponibles para ' . $country->name . '. Puede que aún no tengamos catálogo cargado para este país.',
            ]);
        }

        // 6. Usar código de afiliado en la URL de la tienda
        $affiliateCode = $request->cookie('fl_affiliate_code');
        if ($affiliateCode) {
            $baseShopUrl = rtrim($catalog['country']['shop_url'], '/');
            $catalog['country']['shop_url'] = str_replace('/corp/', "/{$affiliateCode}/", $baseShopUrl);
        }

        // 7. Generar protocolo con IA
        $protocol = $ai->generateProtocol($message, $catalog, $files);

        if (!$protocol) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar el protocolo. Por favor, inténtalo de nuevo.',
            ]);
        }

        $responseTime = round((microtime(true) - $startTime) * 1000);

        // 8. Guardar consulta en fl_consultations
        try {
            FlConsultation::create([
                'country_id' => $country->id,
                'session_id' => session()->getId(),
                'ip_hash' => hash('sha256', $request->ip()),
                'input_type' => count($files) > 0 ? 'mixed' : 'text',
                'input_text' => $message,
                'detected_conditions' => json_encode($conditions),
                'products_sent' => json_encode(array_column($catalog['products'], 'name')),
                'ai_model' => config('services.gemini.model', 'gemini-2.5-flash'),
                'ai_response' => $protocol['raw'],
                'response_time_ms' => $responseTime,
            ]);
        } catch (\Exception $e) {
            Log::warning('FlAdvisor: Error guardando consulta', ['error' => $e->getMessage()]);
        }

        return response()->json([
            'success' => true,
            'response' => $protocol['formatted'],
            'raw' => $protocol['raw'],
            'country' => $country->name,
        ]);
    }

    /**
     * Intenta obtener el nombre del distribuidor desde 4life.com con curl.
     * Cloudflare puede bloquearlo — en ese caso devuelve null.
     */
    private function fetchDistributorName(string $code): ?string
    {
        try {
            $ch = curl_init("https://www.4life.com/{$code}");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_ENCODING => '',
                CURLOPT_HTTPHEADER => [
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                    'Accept-Language: es-ES,es;q=0.9,en;q=0.8',
                    'Accept-Encoding: gzip, deflate, br',
                    'Connection: keep-alive',
                    'Upgrade-Insecure-Requests: 1',
                    'Sec-Fetch-Dest: document',
                    'Sec-Fetch-Mode: navigate',
                    'Sec-Fetch-Site: none',
                    'Sec-Fetch-User: ?1',
                    'Sec-CH-UA: "Not_A Brand";v="8", "Chromium";v="131", "Google Chrome";v="131"',
                    'Sec-CH-UA-Mobile: ?0',
                    'Sec-CH-UA-Platform: "Windows"',
                    'Cache-Control: max-age=0',
                ],
            ]);

            $body = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $body) {
                return $this->extractDistributorName($body);
            }
        } catch (\Exception $e) {
            // Silenciar — no es crítico
        }

        return null;
    }

    /**
     * Extrae el nombre del distribuidor del HTML de 4life.com.
     *
     * <div id="WholesaleInfoTrigger">
     *     <span class="icon-container"><img class="myshop-icon" /></span>
     *     <span>Pepe Megia</span>
     *     <span class="icon-container"><i ...></i></span>
     * </div>
     */
    private function extractDistributorName(string $html): ?string
    {
        if (preg_match('/<div[^>]*id=["\']WholesaleInfoTrigger["\'][^>]*>(.*?)<\/div>/si', $html, $divMatch)) {
            if (preg_match_all('/<span>([^<]+)<\/span>/i', $divMatch[1], $spans)) {
                foreach ($spans[1] as $text) {
                    $text = trim($text);
                    if (strlen($text) > 1) {
                        return $text;
                    }
                }
            }
        }

        return null;
    }

    private function detectCountryCode(Request $request): string
    {
        $prefix = $request->header('X-Prefix', '');

        if ($prefix && str_contains($prefix, '.4life.com')) {
            return str_replace('.4life.com', '', $prefix);
        }

        return 'usspanish';
    }
}
