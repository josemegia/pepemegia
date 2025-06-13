<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Elimina el índice único si existe
        try {
            DB::statement('ALTER TABLE reservas DROP INDEX reservas_mensaje_id_unique');
        } catch (\Throwable $e) {
            logger()->info('No se pudo eliminar índice único mensaje_id (probablemente no existía): ' . $e->getMessage());
        }

        // Agrega un índice normal, sin verificar existencia (MySQL ignorará si ya está)
        Schema::table('reservas', function (Blueprint $table) {
            $table->index('mensaje_id');
        });
    }

    public function down(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->dropIndex(['mensaje_id']);
            $table->unique('mensaje_id'); // Restaura el índice único si hicieras rollback
        });
    }
};
