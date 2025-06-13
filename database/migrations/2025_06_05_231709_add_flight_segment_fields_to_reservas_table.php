<?php
// El archivo de migración recién creado

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
        Schema::table('reservas', function (Blueprint $table) {
            // Campos para el origen del segmento de vuelo
            $table->string('ciudad_origen')->nullable()->after('proveedor');
            $table->string('pais_origen')->nullable()->after('ciudad_origen');
            $table->string('aeropuerto_origen_iata', 3)->nullable()->after('pais_origen');
            $table->string('hora_inicio', 5)->nullable()->after('fecha_inicio'); // Para guardar HH:MM

            // Campos para el destino del segmento de vuelo
            // Renombramos 'ciudad' y 'pais' existentes a 'ciudad_destino' y 'pais_destino'
            $table->renameColumn('ciudad', 'ciudad_destino');
            $table->renameColumn('pais', 'pais_destino');
            $table->string('aeropuerto_destino_iata', 3)->nullable()->after('pais_destino');
            $table->string('hora_fin', 5)->nullable()->after('fecha_fin'); // Para guardar HH:MM

            // Campo para el número de vuelo específico del segmento
            $table->string('numero_vuelo')->nullable()->after('proveedor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->dropColumn([
                'ciudad_origen',
                'pais_origen',
                'aeropuerto_origen_iata',
                'hora_inicio',
                'aeropuerto_destino_iata',
                'hora_fin',
                'numero_vuelo',
            ]);
            $table->renameColumn('ciudad_destino', 'ciudad');
            $table->renameColumn('pais_destino', 'pais');
        });
    }
};