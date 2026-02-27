<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fl_packs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained('fl_countries')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('fl_products')->nullOnDelete()
                  ->comment('Si el pack tiene SKU propio en la tienda');
            $table->string('name', 255);
            $table->enum('type', ['store_pack', 'virtual_pack'])->default('store_pack');
            $table->decimal('price', 12, 2)->nullable();
            $table->decimal('regular_price_sum', 12, 2)->nullable()->comment('Suma de precios individuales');
            $table->decimal('savings', 12, 2)->nullable();
            $table->decimal('savings_pct', 5, 2)->nullable();
            $table->char('currency_code', 3);
            $table->string('url', 500)->nullable();
            $table->boolean('is_available')->default(true);
            $table->string('promotion_details', 255)->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fl_packs');
    }
};
