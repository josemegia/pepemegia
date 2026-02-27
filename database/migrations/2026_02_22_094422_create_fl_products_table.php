<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fl_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained('fl_countries')->cascadeOnDelete();
            $table->foreignId('reference_id')->nullable()->constrained('fl_products_reference')->nullOnDelete();
            $table->string('external_id', 100)->nullable()->comment('SKU o ID en la tienda 4Life');
            $table->string('name', 255);
            $table->string('slug', 255);
            $table->string('url', 500)->nullable();
            $table->string('image_url', 500)->nullable();
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->decimal('price_wholesale', 12, 2)->nullable()->comment('Precio distribuidor');
            $table->decimal('price_loyalty', 12, 2)->nullable()->comment('Precio con puntos lealtad');
            $table->char('currency_code', 3);
            $table->enum('format', [
                'capsules', 'tablets', 'liquid', 'powder',
                'sachets', 'softgels', 'chewables', 'cream', 'spray', 'other'
            ])->nullable();
            $table->unsignedSmallInteger('units_per_container')->nullable()->comment('60, 90, 120 — dato LOCAL');
            $table->string('serving_size', 100)->nullable();
            $table->boolean('is_pack')->default(false);
            $table->boolean('is_available')->default(true);
            $table->boolean('is_on_promotion')->default(false);
            $table->string('promotion_details', 255)->nullable();
            $table->string('category', 100)->nullable();
            $table->unsignedSmallInteger('sort_order')->nullable();
            $table->longText('raw_html')->nullable()->comment('HTML crudo para re-parseo');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['country_id', 'name']);
            $table->index(['country_id', 'is_available']);
            $table->index('reference_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fl_products');
    }
};
