<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveUniqueIndexFromMensajeIdInReservasTable extends Migration
{
    public function up()
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->dropUnique('reservas_mensaje_id_unique');
        });
    }

    public function down()
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->unique('mensaje_id');
        });
    }
}
