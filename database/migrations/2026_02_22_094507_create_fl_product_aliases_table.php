<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fl_product_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reference_id')->constrained('fl_products_reference')->cascadeOnDelete();
            $table->foreignId('country_id')->constrained('fl_countries')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('fl_products')->nullOnDelete()
                  ->comment('Se vincula cuando el producto se scrapea');
            $table->string('local_name', 255);
            $table->enum('match_confidence', ['exact', 'equivalent', 'similar', 'manual'])->default('manual');
            $table->text('composition_notes')->nullable()->comment('Diferencias: 90 caps vs 60 en USA');
            $table->boolean('is_confirmed')->default(false)->comment('Admin revisó este mapeo');
            $table->timestamps();

            $table->unique(['reference_id', 'country_id', 'local_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fl_product_aliases');
    }
};
