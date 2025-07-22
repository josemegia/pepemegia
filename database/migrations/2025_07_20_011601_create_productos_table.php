<?php

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
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('articulo');
            $table->text('descripcion');
            $table->string('iso2', 2);
            $table->text('imagenb64');
            $table->decimal('precio', 8, 2);
            $table->decimal('precio_tienda', 8, 2);
            $table->decimal('precio_afiliado', 8, 2);
            $table->decimal('precio_oferta', 8, 2);
            $table->decimal('LPS', 8, 2);
            $table->decimal('LealtadLPS', 8, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};