<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fl_products_reference', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->string('category', 100)->nullable()->comment('Immune System, Energy, Targeted TF...');
            $table->enum('format', [
                'capsules', 'tablets', 'liquid', 'powder',
                'sachets', 'softgels', 'chewables', 'cream', 'spray', 'other'
            ])->default('capsules');
            $table->string('serving_size', 100)->nullable()->comment('2 capsules, 1 scoop (5g)');
            $table->unsignedSmallInteger('servings_per_container')->nullable();
            $table->json('ingredients')->nullable()->comment('Supplement facts estructurados');
            $table->text('key_ingredients_summary')->nullable()->comment('Resumen legible para IA');
            $table->text('mechanism')->nullable()->comment('Mecanismo de acción');
            $table->text('dosage_instructions')->nullable()->comment('Take 2 capsules daily...');
            $table->text('benefits')->nullable();
            $table->text('warnings')->nullable();
            $table->string('image_url', 500)->nullable();
            $table->string('source_country', 30)->default('us')->comment('us o europe');
            $table->string('source_url', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fl_products_reference');
    }
};
