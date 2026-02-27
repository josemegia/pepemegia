<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FlHealthCondition;

class FlHealthConditionsSeeder extends Seeder
{
    public function run(): void
    {
        $conditions = [
            // IMMUNE
            ['name' => 'Immune System Support', 'slug' => 'immune-system', 'category' => 'Immune', 'description' => 'General immune system support and defense.'],
            ['name' => 'Antioxidant Protection', 'slug' => 'antioxidant', 'category' => 'Immune', 'description' => 'Protection against oxidative stress and free radicals.'],

            // CARDIOVASCULAR
            ['name' => 'Cardiovascular Health', 'slug' => 'cardiovascular', 'category' => 'Cardiovascular', 'description' => 'Heart, circulatory and vascular health support.'],
            ['name' => 'Cholesterol Management', 'slug' => 'cholesterol', 'category' => 'Cardiovascular', 'description' => 'Support for healthy cholesterol levels.'],

            // BRAIN & NERVOUS SYSTEM
            ['name' => 'Brain Health & Memory', 'slug' => 'brain-memory', 'category' => 'Brain & Nervous', 'description' => 'Cognitive function, memory, concentration and brain health.'],
            ['name' => 'Stress & Mood Support', 'slug' => 'stress-mood', 'category' => 'Brain & Nervous', 'description' => 'Stress management, mood balance and relaxation.'],
            ['name' => 'Sleep Quality', 'slug' => 'sleep', 'category' => 'Brain & Nervous', 'description' => 'Support for falling asleep faster and improving sleep quality.'],

            // DIGESTIVE
            ['name' => 'Digestive Health', 'slug' => 'digestive', 'category' => 'Digestive', 'description' => 'Gastrointestinal health, gut flora and digestion.'],
            ['name' => 'Liver & Detox', 'slug' => 'liver-detox', 'category' => 'Digestive', 'description' => 'Liver function, cleansing and detoxification.'],
            ['name' => 'Gut Microbiome', 'slug' => 'gut-microbiome', 'category' => 'Digestive', 'description' => 'Probiotic and prebiotic support for intestinal flora.'],

            // METABOLIC
            ['name' => 'Blood Sugar Management', 'slug' => 'blood-sugar', 'category' => 'Metabolic', 'description' => 'Healthy glucose metabolism and blood sugar levels.'],
            ['name' => 'Weight Management', 'slug' => 'weight-management', 'category' => 'Metabolic', 'description' => 'Weight control, fat burning and appetite management.'],
            ['name' => 'Thyroid Support', 'slug' => 'thyroid', 'category' => 'Metabolic', 'description' => 'Healthy thyroid function and metabolism.'],

            // MUSCULOSKELETAL
            ['name' => 'Joint Health & Flexibility', 'slug' => 'joint-health', 'category' => 'Musculoskeletal', 'description' => 'Joint support, flexibility and mobility.'],
            ['name' => 'Bone Health', 'slug' => 'bone-health', 'category' => 'Musculoskeletal', 'description' => 'Bone density, structure and metabolism.'],
            ['name' => 'Muscle Support & Recovery', 'slug' => 'muscle-support', 'category' => 'Musculoskeletal', 'description' => 'Muscle growth, repair, strength and recovery.'],

            // SKIN, HAIR & BEAUTY
            ['name' => 'Skin Health & Beauty', 'slug' => 'skin-health', 'category' => 'Beauty', 'description' => 'Skin elasticity, hydration, appearance and anti-aging.'],
            ['name' => 'Hair & Nails', 'slug' => 'hair-nails', 'category' => 'Beauty', 'description' => 'Healthy hair growth and strong nails.'],

            // AGING
            ['name' => 'Healthy Aging & Longevity', 'slug' => 'healthy-aging', 'category' => 'Aging', 'description' => 'Cellular aging, longevity and age-related health support.'],

            // RESPIRATORY
            ['name' => 'Respiratory Health', 'slug' => 'respiratory', 'category' => 'Respiratory', 'description' => 'Lung function, airways and respiratory defense.'],

            // VISION
            ['name' => 'Eye Health & Vision', 'slug' => 'eye-health', 'category' => 'Vision', 'description' => 'Visual acuity, macular health and eye function.'],

            // GENDER-SPECIFIC
            ['name' => "Women's Health", 'slug' => 'womens-health', 'category' => 'Gender-Specific', 'description' => 'Female reproductive health, hormonal balance, menopause support.'],
            ['name' => "Men's Health", 'slug' => 'mens-health', 'category' => 'Gender-Specific', 'description' => 'Prostate health, male vitality and hormonal support.'],

            // URINARY
            ['name' => 'Urinary Tract Health', 'slug' => 'urinary-health', 'category' => 'Urinary', 'description' => 'Kidney, bladder and urinary tract support.'],

            // ENERGY & PERFORMANCE
            ['name' => 'Energy & Vitality', 'slug' => 'energy-vitality', 'category' => 'Energy', 'description' => 'Physical energy, fatigue reduction and vitality.'],
            ['name' => 'Sports Performance', 'slug' => 'sports-performance', 'category' => 'Energy', 'description' => 'Athletic performance, endurance and exercise support.'],

            // GENERAL
            ['name' => 'General Wellness & Nutrition', 'slug' => 'general-wellness', 'category' => 'General', 'description' => 'Overall health, multivitamin and general nutrition.'],
            ['name' => "Children's Health", 'slug' => 'childrens-health', 'category' => 'General', 'description' => 'Nutritional support for children and adolescents.'],

            // ORAL CARE
            ['name' => 'Oral Health', 'slug' => 'oral-health', 'category' => 'Oral', 'description' => 'Dental health, oral microbiome and gum support.'],
        ];

        foreach ($conditions as $condition) {
            FlHealthCondition::updateOrCreate(
                ['slug' => $condition['slug']],
                $condition
            );
        }
    }
}
