<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('descargas', function (Blueprint $table) {
            $table->boolean('eliminado')->default(false)->after('exitosa');
        });
    }

    public function down(): void
    {
        Schema::table('descargas', function (Blueprint $table) {
            $table->dropColumn('eliminado');
        });
    }
};
