<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservas', function (Blueprint $table) {
            $table->id();
            $table->string('email_origen'); // josemegia@gmail.com o claudiamegia@gmail.com
            $table->string('tipo_reserva'); // hotel, vuelo, coche, tren, autobus, vivienda
            $table->string('proveedor')->nullable(); // Booking, Airbnb, Renfe, etc.
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->string('ciudad')->nullable();
            $table->string('pais')->nullable();
            $table->text('direccion')->nullable();
            $table->string('numero_reserva')->nullable();
            $table->decimal('precio', 10, 2)->nullable();
            $table->string('moneda', 3)->nullable();
            $table->json('datos_adicionales')->nullable(); // Para información específica
            $table->text('contenido_email')->nullable(); // Email original para referencia
            $table->string('mensaje_id')->unique(); // ID único del email
            $table->timestamps();
            
            $table->index(['email_origen', 'fecha_inicio']);
            $table->index(['tipo_reserva', 'fecha_inicio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservas');
    }
};