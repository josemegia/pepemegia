<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FlProductReference;
use App\Models\FlHealthCondition;
use Illuminate\Support\Facades\DB;

class FlProductsReferenceUpdateSeeder extends Seeder
{
    public function run(): void
    {
        // Add RioVida Liquid
        FlProductReference::updateOrCreate(
            ['slug' => 'riovida-liquid'],
            [
                'name' => '4Life Transfer Factor RioVida',
                'slug' => 'riovida-liquid',
                'category' => 'RioVida',
                'format' => 'liquid',
                'serving_size' => '30 ml (1 fl oz)',
                'servings_per_container' => 18,
                'ingredients' => json_encode([
                    ['name' => '4Life Tri-Factor Formula (UltraFactor, OvoFactor, NanoFactor)', 'amount' => '600', 'unit' => 'mg', 'details' => 'from cow colostrum and egg yolk'],
                    ['name' => 'Vitamin C', 'amount' => null, 'unit' => 'mg', 'details' => null],
                    ['name' => 'RioVida Superfruit Blend (açaí, pomegranate, aronia, maqui, blueberry, elderberry)', 'amount' => null, 'unit' => 'ml', 'details' => 'antioxidant juice blend'],
                ], JSON_UNESCAPED_UNICODE),
                'key_ingredients_summary' => 'Tri-Factor 600mg + vitamina C + superfrutas (açaí, granada, aronia, maqui, arándano, saúco). Shot líquido. 94% más potencia antioxidante que fórmula original.',
                'mechanism' => 'Shot líquido de superfrutas con Transfer Factor. Activa sistema inmunológico en 2 horas. Alta potencia antioxidante. Respalda energía, salud cerebral y cardiovascular. Contribuye al envejecimiento celular saludable.',
                'dosage_instructions' => 'Tomar 1 o más onzas (30 ml) al día. Refrigerar después de abrir. Apto desde los 2 años.',
                'benefits' => 'Respaldo inmunológico + antioxidante potente en formato líquido listo para consumir.',
                'warnings' => 'Contiene derivados de leche (calostro) y huevo (yema). Refrigerar después de abrir.',
                'source_url' => null,
                'image_url' => null,
                'is_active' => true,
            ]
        );

        // Link RioVida Liquid to health conditions
        $product = FlProductReference::where('slug', 'riovida-liquid')->first();
        if ($product) {
            $conditionMappings = [
                'immune-system' => 'primary',
                'antioxidant' => 'primary',
                'healthy-aging' => 'secondary',
                'general-wellness' => 'secondary',
                'brain-memory' => 'complementary',
                'cardiovascular' => 'complementary',
                'energy-vitality' => 'complementary',
            ];

            foreach ($conditionMappings as $conditionSlug => $relevance) {
                $condition = FlHealthCondition::where('slug', $conditionSlug)->first();
                if (!$condition) continue;

                DB::table('fl_reference_conditions')->updateOrInsert(
                    [
                        'reference_id' => $product->id,
                        'condition_id' => $condition->id,
                    ],
                    [
                        'relevance' => $relevance,
                        'notes' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}
