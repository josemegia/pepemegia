<?php

namespace App\Services;

use App\Models\FlCountry;
use App\Models\FlHealthCondition;
use App\Models\FlProduct;
use App\Models\FlProductReference;

class CatalogService
{
    /**
     * Busca productos relevantes para una lista de condiciones y un país.
     *
     * @param array  $conditionNames  Nombres de condiciones detectadas por la IA
     * @param string $countryCode     Código del país (formato BD: "spain", "colombia")
     * @param int    $limit           Máximo de productos a devolver
     * @return array ['country' => ..., 'products' => [...], 'source' => 'local'|'reference']
     */
    public function getRelevantProducts(array $conditionNames, string $countryCode, int $limit = 15): array
    {
        $country = FlCountry::where('code', $countryCode)
            ->where('is_active', true)
            ->first();

        if (!$country) {
            return ['country' => null, 'products' => [], 'source' => 'none'];
        }

        // 1. Buscar condiciones que coincidan
        $conditionIds = $this->matchConditions($conditionNames);

        if (empty($conditionIds)) {
            // Si no matchea ninguna condición, devolver todo el catálogo del país (limitado)
            return $this->fallbackCatalog($country, $limit);
        }

        // 2. Buscar referencias vinculadas a esas condiciones, ordenadas por relevancia
        $references = FlProductReference::active()
            ->forConditions($conditionIds)
            ->with(['conditions' => function ($q) use ($conditionIds) {
                $q->whereIn('fl_health_conditions.id', $conditionIds);
            }])
            ->get()
            ->sortByDesc(function ($ref) {
                // Priorizar: primary > secondary > complementary
                $best = $ref->conditions->min(function ($c) {
                    return match ($c->pivot->relevance) {
                        'primary'       => 1,
                        'secondary'     => 2,
                        'complementary' => 3,
                        default         => 4,
                    };
                });
                return -$best; // negativo para sortByDesc
            })
            ->take($limit);

        // 3. Para cada referencia, buscar producto local del país
        $products = [];
        $source = 'reference';

        foreach ($references as $ref) {
            $localProduct = FlProduct::where('reference_id', $ref->id)
                ->where('country_id', $country->id)
                ->first();

            if ($localProduct) {
                $source = 'local';
                $products[] = $localProduct->toAiArray();
            } else {
                // Sin producto local: enviar datos de referencia
                $products[] = $this->referenceToAiArray($ref, $country);
            }
        }

        return [
            'country' => [
                'code' => $country->code,
                'name' => $country->name,
                'currency' => $country->currency_code,
                'shop_url' => $country->shop_url,
            ],
            'products' => $products,
            'source' => $source,
            'matched_conditions' => $conditionIds,
        ];
    }

    /**
     * Busca condiciones por nombre y aliases.
     */
    private function matchConditions(array $names): array
    {
        $ids = [];

        foreach ($names as $name) {
            $name = mb_strtolower(trim($name));
            if (empty($name)) continue;

            $conditions = FlHealthCondition::all()->filter(function ($c) use ($name) {
                return $c->matchesTerm($name);
            });

            foreach ($conditions as $c) {
                $ids[] = $c->id;
            }
        }

        return array_unique($ids);
    }

    /**
     * Convierte una referencia a formato AI cuando no hay producto local.
     */
    private function referenceToAiArray(FlProductReference $ref, FlCountry $country): array
    {
        return [
            'id'                  => 'ref_' . $ref->id,
            'name'                => $ref->name,
            'format'              => $ref->format,
            'units_per_container' => $ref->servings_per_container,
            'serving_size'        => $ref->serving_size,
            'dosage'              => $ref->dosage_instructions,
            'key_ingredients'     => $ref->key_ingredients_summary,
            'mechanism'           => $ref->mechanism,
            'benefits'            => $ref->benefits,
            'price'               => null,
            'currency'            => $country->currency_code,
            'is_available'        => true,
            'is_on_promotion'     => false,
            'promotion_details'   => null,
            'url'                 => $country->shop_url,
            'source'              => 'reference (no local product yet)',
        ];
    }

    /**
     * Fallback: devuelve catálogo general si no se identificaron condiciones.
     */
    private function fallbackCatalog(FlCountry $country, int $limit): array
    {
        // Primero intentar productos locales
        $locals = FlProduct::where('country_id', $country->id)
            ->where('is_available', true)
            ->limit($limit)
            ->get();

        if ($locals->isNotEmpty()) {
            return [
                'country' => [
                    'code' => $country->code,
                    'name' => $country->name,
                    'currency' => $country->currency_code,
                    'shop_url' => $country->shop_url,
                ],
                'products' => $locals->map->toAiArray()->toArray(),
                'source' => 'local_fallback',
                'matched_conditions' => [],
            ];
        }

        // Si no hay locales, enviar todas las referencias activas
        $refs = FlProductReference::active()->limit($limit)->get();

        return [
            'country' => [
                'code' => $country->code,
                'name' => $country->name,
                'currency' => $country->currency_code,
                'shop_url' => $country->shop_url,
            ],
            'products' => $refs->map(fn($r) => $this->referenceToAiArray($r, $country))->toArray(),
            'source' => 'reference_fallback',
            'matched_conditions' => [],
        ];
    }
}
