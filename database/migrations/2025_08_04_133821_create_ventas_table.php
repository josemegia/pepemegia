<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->string('pais_iso2', 5);
            $table->string('idioma', 10);

            // Campos base (usuario los introduce)
            $table->decimal('precio_afiliado', 12, 2);
            $table->decimal('precio_tienda', 12, 2);
            $table->decimal('pvp', 12, 2);
            $table->decimal('precio2_paquete_mes4', 12, 2);
            $table->decimal('precio1_paquete_mes4', 12, 2);
            $table->decimal('propuesta_mensual', 12, 2);
            $table->decimal('precio_paquete', 12, 2);

            // Campos calculados automáticamente
            $table->decimal('ganancia_mes1', 12, 2)->nullable();
            $table->decimal('ganancia1_mes4', 12, 2)->nullable();
            $table->decimal('ganancia2_mes4', 12, 2)->nullable();
            $table->decimal('precio_mes1', 12, 2)->nullable();
            $table->decimal('precio1_mes4_calc', 12, 2)->nullable();
            $table->decimal('ganancia_paquete_mes1', 12, 2)->nullable();
            $table->decimal('ganancia1_paquete_mes4', 12, 2)->nullable();
            $table->decimal('ganancia2_paquete_mes4', 12, 2)->nullable();
            $table->decimal('ganancia_total_mes1', 12, 2)->nullable();
            $table->decimal('ganancia_paquete_mes2', 12, 2)->nullable();
            $table->decimal('ganancia_total_mes2', 12, 2)->nullable();
            $table->decimal('ganancia_total_mes4', 12, 2)->nullable();

            $table->timestamps();

            $table->unique(['pais_iso2', 'idioma']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('ventas');
    }
};
