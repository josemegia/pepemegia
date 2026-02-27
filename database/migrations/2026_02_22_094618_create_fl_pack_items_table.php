<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fl_pack_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pack_id')->constrained('fl_packs')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('fl_products')->cascadeOnDelete();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->timestamps();

            $table->unique(['pack_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fl_pack_items');
    }
};
