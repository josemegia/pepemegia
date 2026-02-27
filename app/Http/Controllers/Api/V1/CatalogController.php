<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FlCountry;
use App\Models\FlHealthCondition;
use App\Models\FlProductReference;
use App\Services\CatalogService;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function __construct(
        private CatalogService $catalog
    ) {}

    /**
     * GET /api/v1/products?country=spain&conditions[]=insomnio&conditions[]=fatiga
     */
    public function products(Request $request)
    {
        $countryCode = $request->query('country', 'us');
        $conditions = $request->query('conditions', []);

        $result = $this->catalog->getRelevantProducts($conditions, $countryCode);

        return response()->json([
            'success' => true,
            'data' => $result['products'],
            'meta' => [
                'country' => $result['country'],
                'source' => $result['source'],
                'total' => count($result['products']),
            ]
        ]);
    }

    /**
     * GET /api/v1/conditions
     */
    public function conditions()
    {
        $conditions = FlHealthCondition::select('id', 'name', 'category', 'aliases')
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $conditions,
            'meta' => ['total' => $conditions->count()]
        ]);
    }

    /**
     * GET /api/v1/countries
     */
    public function countries()
    {
        $countries = FlCountry::where('is_active', true)
            ->select('code', 'name', 'currency_code', 'shop_url')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $countries,
            'meta' => ['total' => $countries->count()]
        ]);
    }
}
