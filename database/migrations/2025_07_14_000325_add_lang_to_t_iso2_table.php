<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Añade la columna 'lang' a la tabla 't_iso2'
        Schema::table('t_iso2', function (Blueprint $table) {
            // Se añade un campo para el código de idioma ISO 639-1 (2 caracteres)
            // Se coloca después de la columna 'pais' para mantener el orden lógico.
            // Se permite que sea nulo por si algún código de país no tiene un idioma principal claro.
            $table->string('lang', 2)->nullable()->after('pais');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Lógica para revertir la migración, eliminando la columna
        Schema::table('t_iso2', function (Blueprint $table) {
            $table->dropColumn('lang');
        });
    }
};