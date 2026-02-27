<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fl_reference_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reference_id')->constrained('fl_products_reference')->cascadeOnDelete();
            $table->foreignId('condition_id')->constrained('fl_health_conditions')->cascadeOnDelete();
            $table->enum('relevance', ['primary', 'secondary', 'complementary'])->default('primary');
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->unique(['reference_id', 'condition_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fl_reference_conditions');
    }
};
