<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateReservasUsePasajeroId extends Migration
{
    public function up()
    {
        Schema::table('reservas', function (Blueprint $table) {
            if (Schema::hasColumn('reservas', 'pasajero')) {
                $table->dropColumn('pasajero'); // elimina columna antigua
            }
            if (!Schema::hasColumn('reservas', 'pasajero_id')) {
                $table->foreignId('pasajero_id')->nullable()->after('email_origen')->constrained()->nullOnDelete();
            }
        });
    }

    public function down()
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->string('pasajero')->nullable();
            $table->dropForeign(['pasajero_id']);
            $table->dropColumn('pasajero_id');
        });
    }
}
