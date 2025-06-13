<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaisNormalizadoToIsoCountryCodesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('t_iso2', function (Blueprint $table) {
            $table->string('pais_normalizado')->nullable()->after('pais')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_iso2', function (Blueprint $table) {
            $table->dropIndex(['pais_normalizado']);
            $table->dropColumn('pais_normalizado');
        });
    }
}

