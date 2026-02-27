<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FlCountry;
use App\Services\AIAdvisorService;
use App\Services\CatalogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ConsultationController extends Controller
{
    public function __construct(
        private AIAdvisorService $aiAdvisor,
        private CatalogService $catalog
    ) {}

    /**
     * POST /api/v1/consultation
     * Flujo completo: analiza mensaje → busca productos → genera protocolo
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:2000',
            'country_code' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $validator->errors()
            ], 422);
        }

        $countryCode = $request->country_code ?? 'us';

        // 1. Obtener países disponibles
        $availableCountries = FlCountry::where('is_active', true)
            ->pluck('name', 'code')
            ->toArray();

        // 2. Analizar mensaje con IA (detectar país y condiciones)
        $analysis = $this->aiAdvisor->analyzeMessage(
            $request->message,
            $countryCode,
            $availableCountries
        );

        // 3. Buscar productos relevantes
        $catalog = $this->catalog->getRelevantProducts(
            $analysis['conditions'] ?? [],
            $analysis['country_code'] ?? $countryCode
        );

        // 4. Generar protocolo
        $protocol = null;
        if (!empty($catalog['products'])) {
            $protocol = $this->aiAdvisor->generateProtocol(
                $request->message,
                $catalog
            );
        }

        return response()->json([
            'success' => true,
            'data' => [
                'analysis' => $analysis,
                'protocol' => $protocol,
                'catalog' => $catalog,
            ],
            'usage' => [
                'requests_today' => $request->api_client->requests_today,
                'daily_limit' => $request->api_client->daily_limit,
            ]
        ]);
    }

    /**
     * POST /api/v1/chat
     * Chat simplificado: mensaje → protocolo directo
     */
    public function chat(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:2000',
            'country_code' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $validator->errors()
            ], 422);
        }

        $countryCode = $request->country_code ?? 'us';

        $availableCountries = FlCountry::where('is_active', true)
            ->pluck('name', 'code')
            ->toArray();

        $analysis = $this->aiAdvisor->analyzeMessage(
            $request->message,
            $countryCode,
            $availableCountries
        );

        $catalog = $this->catalog->getRelevantProducts(
            $analysis['conditions'] ?? [],
            $analysis['country_code'] ?? $countryCode
        );

        $protocol = null;
        if (!empty($catalog['products'])) {
            $protocol = $this->aiAdvisor->generateProtocol(
                $request->message,
                $catalog
            );
        }

        return response()->json([
            'success' => true,
            'data' => [
                'response' => $protocol['formatted'] ?? 'No se encontraron productos para tu consulta.',
                'raw' => $protocol['raw'] ?? null,
                'country' => $catalog['country'],
                'conditions' => $analysis['conditions'] ?? [],
            ]
        ]);
    }
}
