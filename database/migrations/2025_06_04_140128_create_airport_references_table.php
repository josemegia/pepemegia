<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('airport_references', function (Blueprint $table) {
            $table->id();
            $table->string('identifier_type'); // 'iata' o 'city'
            $table->string('identifier_value')->index(); // El código IATA o el nombre de la ciudad normalizado
            $table->string('country_name');
            $table->timestamps();

            $table->unique(['identifier_type', 'identifier_value']); // Asegura que no haya duplicados
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('airport_references');
    }
};