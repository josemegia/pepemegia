<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FlProductReference;
use App\Models\FlHealthCondition;
use Illuminate\Support\Facades\DB;

class FlReferenceConditionsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('fl_reference_conditions')->truncate();

        // product_slug => [condition_slug => relevance]
        $mappings = [

            // CORE IMMUNE
            'transfer-factor-plus-tri-factor' => ['immune-system' => 'primary', 'antioxidant' => 'secondary', 'general-wellness' => 'complementary'],
            'transfer-factor-tri-factor' => ['immune-system' => 'primary', 'general-wellness' => 'secondary'],
            'transfer-factor-classic' => ['immune-system' => 'primary'],
            'transfer-factor-chewable' => ['immune-system' => 'primary', 'general-wellness' => 'secondary'],
            'transfer-factor-immune-spray' => ['immune-system' => 'primary', 'respiratory' => 'complementary'],
            'transfer-factor-renewall' => ['skin-health' => 'primary', 'immune-system' => 'complementary'],
            'transfer-factor-immune-boost' => ['immune-system' => 'primary', 'antioxidant' => 'secondary', 'energy-vitality' => 'complementary'],
            'tf-boost-sachets' => ['immune-system' => 'primary', 'antioxidant' => 'secondary', 'energy-vitality' => 'complementary'],

            // TARGETED TRANSFER FACTOR
            'transfer-factor-cardio' => ['cardiovascular' => 'primary', 'immune-system' => 'secondary', 'antioxidant' => 'secondary'],
            'transfer-factor-recall' => ['brain-memory' => 'primary', 'immune-system' => 'secondary', 'healthy-aging' => 'complementary'],
            'transfer-factor-glucoach' => ['blood-sugar' => 'primary', 'immune-system' => 'secondary', 'antioxidant' => 'complementary'],
            'transfer-factor-agepro' => ['healthy-aging' => 'primary', 'antioxidant' => 'primary', 'immune-system' => 'secondary', 'general-wellness' => 'complementary'],
            'transfer-factor-collagen' => ['skin-health' => 'primary', 'healthy-aging' => 'primary', 'joint-health' => 'secondary', 'muscle-support' => 'secondary', 'hair-nails' => 'secondary', 'immune-system' => 'complementary', 'antioxidant' => 'complementary'],
            'transfer-factor-collagen-type-i' => ['skin-health' => 'primary', 'healthy-aging' => 'primary', 'hair-nails' => 'secondary', 'muscle-support' => 'secondary', 'immune-system' => 'complementary'],
            'nanofactor-glutamine-prime' => ['immune-system' => 'primary', 'general-wellness' => 'primary', 'energy-vitality' => 'secondary', 'antioxidant' => 'secondary', 'digestive' => 'complementary'],
            'transfer-factor-metabolite' => ['thyroid' => 'primary', 'immune-system' => 'secondary', 'weight-management' => 'secondary'],
            'transfer-factor-kbu' => ['urinary-health' => 'primary', 'immune-system' => 'secondary', 'antioxidant' => 'complementary', 'liver-detox' => 'complementary'],
            'transfer-factor-lung' => ['respiratory' => 'primary', 'immune-system' => 'secondary'],
            'transfer-factor-vista' => ['eye-health' => 'primary', 'immune-system' => 'secondary', 'antioxidant' => 'secondary'],
            'transfer-factor-belle-vie' => ['womens-health' => 'primary', 'immune-system' => 'secondary', 'antioxidant' => 'complementary'],
            'transfer-factor-malepro' => ['mens-health' => 'primary', 'immune-system' => 'secondary', 'antioxidant' => 'complementary'],
            'transfer-factor-reflexion' => ['stress-mood' => 'primary', 'brain-memory' => 'secondary', 'immune-system' => 'complementary'],
            'transfer-factor-sleeprite' => ['sleep' => 'primary', 'stress-mood' => 'secondary', 'immune-system' => 'complementary'],

            // RIOVIDA
            'riovida-stix' => ['immune-system' => 'primary', 'antioxidant' => 'primary', 'healthy-aging' => 'secondary', 'general-wellness' => 'secondary', 'energy-vitality' => 'complementary'],
            'riovida-burst' => ['immune-system' => 'primary', 'antioxidant' => 'primary', 'energy-vitality' => 'complementary'],
            'riovida-chews' => ['immune-system' => 'primary', 'antioxidant' => 'primary'],

            // RITESTART
            'ritestart' => ['general-wellness' => 'primary', 'immune-system' => 'primary', 'cardiovascular' => 'secondary', 'antioxidant' => 'secondary', 'joint-health' => 'complementary', 'eye-health' => 'complementary'],
            'ritestart-kids-teens' => ['childrens-health' => 'primary', 'immune-system' => 'primary', 'general-wellness' => 'primary', 'bone-health' => 'secondary', 'brain-memory' => 'complementary'],
            'nutrastart-blue-vanilla' => ['general-wellness' => 'primary', 'weight-management' => 'secondary', 'immune-system' => 'secondary'],

            // DIGEST4LIFE
            'pre-o-biotics' => ['digestive' => 'primary', 'gut-microbiome' => 'primary', 'immune-system' => 'primary'],
            'aloe-vera-stix' => ['digestive' => 'primary', 'immune-system' => 'secondary', 'general-wellness' => 'complementary'],
            'digestive-enzymes' => ['digestive' => 'primary', 'liver-detox' => 'complementary'],
            'fibre-system-plus' => ['digestive' => 'primary', 'liver-detox' => 'primary', 'weight-management' => 'complementary'],
            'super-detox' => ['liver-detox' => 'primary', 'digestive' => 'primary', 'antioxidant' => 'complementary'],
            'phytolax' => ['liver-detox' => 'primary', 'digestive' => 'secondary'],
            'tea4life' => ['liver-detox' => 'primary', 'digestive' => 'secondary'],

            // 4LIFETRANSFORM
            'pro-tf-protein' => ['weight-management' => 'primary', 'muscle-support' => 'primary', 'sports-performance' => 'primary', 'immune-system' => 'secondary'],
            'transform-prezoom' => ['sports-performance' => 'primary', 'muscle-support' => 'primary', 'immune-system' => 'secondary', 'brain-memory' => 'complementary'],
            'transfer-factor-renuvo' => ['immune-system' => 'primary', 'healthy-aging' => 'primary', 'antioxidant' => 'primary', 'energy-vitality' => 'secondary'],
            'transform-burn' => ['weight-management' => 'primary', 'energy-vitality' => 'secondary'],
            'shaperite' => ['weight-management' => 'primary', 'blood-sugar' => 'secondary', 'cardiovascular' => 'complementary'],
            'transform-woman' => ['womens-health' => 'primary', 'healthy-aging' => 'primary', 'skin-health' => 'secondary'],
            'transform-man' => ['mens-health' => 'primary', 'bone-health' => 'primary', 'healthy-aging' => 'secondary', 'muscle-support' => 'secondary'],
            'transform-protein-bar' => ['muscle-support' => 'primary', 'sports-performance' => 'secondary', 'weight-management' => 'secondary'],

            // ENERGY
            'energy-go-stix' => ['energy-vitality' => 'primary', 'weight-management' => 'secondary', 'immune-system' => 'secondary'],

            // 4LIFE ELEMENTS
            'zinc-factor' => ['immune-system' => 'primary'],
            'gold-factor' => ['healthy-aging' => 'primary', 'brain-memory' => 'secondary', 'joint-health' => 'secondary'],

            // 4LIFE FUNDAMENTALS
            'essential-fatty-acid-complex' => ['cardiovascular' => 'primary', 'brain-memory' => 'primary', 'weight-management' => 'secondary', 'general-wellness' => 'complementary'],
            'cal-mag-complex' => ['bone-health' => 'primary', 'joint-health' => 'secondary', 'muscle-support' => 'complementary'],
            'fibro-amj' => ['joint-health' => 'primary', 'bone-health' => 'secondary', 'muscle-support' => 'secondary'],
            'flex4life' => ['joint-health' => 'primary', 'muscle-support' => 'secondary', 'antioxidant' => 'complementary'],
            'fortified-colostrum' => ['immune-system' => 'primary', 'digestive' => 'primary', 'respiratory' => 'secondary', 'healthy-aging' => 'complementary'],
            'gurmar' => ['blood-sugar' => 'primary', 'weight-management' => 'secondary'],
            'life-c-chewable' => ['antioxidant' => 'primary', 'immune-system' => 'secondary', 'general-wellness' => 'complementary'],
            'menopause-support' => ['womens-health' => 'primary', 'stress-mood' => 'secondary'],
            'multiplex' => ['general-wellness' => 'primary'],
            'musculoskeletal-formula' => ['muscle-support' => 'primary', 'bone-health' => 'primary', 'joint-health' => 'primary'],
            'stress-formula' => ['stress-mood' => 'primary', 'sleep' => 'secondary'],
            '4life-fortify' => ['general-wellness' => 'primary', 'immune-system' => 'secondary', 'childrens-health' => 'complementary'],

            // SKINCARE
            'akwa-oil-to-foam-cleanser' => ['skin-health' => 'primary'],
            'akwa-four-in-one-toner' => ['skin-health' => 'primary'],
            'akwa-vitamin-serum' => ['skin-health' => 'primary', 'antioxidant' => 'complementary'],
            'akwa-revitalizing-eye-cream' => ['skin-health' => 'primary'],
            'akwa-moisturizing-cream' => ['skin-health' => 'primary', 'healthy-aging' => 'complementary'],
            'akwa-volcanic-mud-mask' => ['skin-health' => 'primary'],
            'akwa-spf30-sunscreen' => ['skin-health' => 'primary'],
            'akwa-sheet-mask' => ['skin-health' => 'primary', 'immune-system' => 'complementary'],

            // ENUMMI
            'enummi-toothpaste' => ['oral-health' => 'primary'],
            'enummi-body-lotion' => ['skin-health' => 'primary'],
            'enummi-shower-gel' => ['skin-health' => 'primary'],
            'enummi-shampoo' => ['hair-nails' => 'primary'],
            'enummi-conditioner' => ['hair-nails' => 'primary'],
        ];

        foreach ($mappings as $productSlug => $conditions) {
            $product = FlProductReference::where('slug', $productSlug)->first();
            if (!$product) continue;

            foreach ($conditions as $conditionSlug => $relevance) {
                $condition = FlHealthCondition::where('slug', $conditionSlug)->first();
                if (!$condition) continue;

                DB::table('fl_reference_conditions')->insert([
                    'reference_id' => $product->id,
                    'condition_id' => $condition->id,
                    'relevance' => $relevance,
                    'notes' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
