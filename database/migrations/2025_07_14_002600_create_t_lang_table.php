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
        // Crea la tabla 't_lang' para mapear códigos de idioma a códigos de país (banderas)
        Schema::create('t_lang', function (Blueprint $table) {
            // Este será el código de idioma, como 'en', 'he', 'pt_BR'.
            // Lo definimos como la llave primaria porque será único.
            $table->string('lang_code', 10)->primary();

            // Este será el código de país para la bandera, como 'us', 'il', 'br'.
            $table->string('country_code', 2);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('t_lang');
    }
};

