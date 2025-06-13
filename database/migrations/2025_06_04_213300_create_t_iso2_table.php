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
    public function up(): void
    {
        // Comprueba si la tabla ya existe antes de intentar crearla
        if (!Schema::hasTable('t_iso2')) {
            Schema::create('t_iso2', function (Blueprint $table) {
                // Definimos la columna iso2 como char(2) y clave primaria
                // Laravel no tiene un tipo 'char' directo, usamos string y lo ajustamos
                // o usamos un statement DB::statement para total control si es necesario,
                // pero para este caso, string(2) es lo más cercano y funcional.
                // Para bases de datos como MySQL, string(2) se traduce a VARCHAR(2).
                // Si necesitas estrictamente CHAR(2), puedes usar DB::statement o ajustar
                // la columna después de su creación si el ORM no lo hace exactamente como CHAR.
                // Sin embargo, para la funcionalidad, VARCHAR(2) suele ser indistinguible.
                $table->string('iso2', 2); // VARCHAR(2)
                $table->string('pais', 255); // VARCHAR(255)

                // Establecer iso2 como clave primaria
                $table->primary('iso2');

                // No se definen timestamps si la tabla no los tiene
                // $table->timestamps();
            });

            // Si necesitas que iso2 sea estrictamente CHAR(2) en MySQL y el ORM lo creó como VARCHAR(2)
            // podrías añadir un statement raw DESPUÉS de Schema::create si es necesario (avanzado):
            // if (DB::connection()->getDriverName() == 'mysql') {
            //     DB::statement("ALTER TABLE t_iso2 MODIFY iso2 CHAR(2) NOT NULL");
            // }
            // Pero usualmente, con string(2) y estableciéndolo como primary key es suficiente.
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('t_iso2');
    }
};