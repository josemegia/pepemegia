<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fl_health_conditions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 150)->unique();
            $table->json('aliases')->nullable()->comment('["inmunidad", "defensas", "immune"]');
            $table->string('category', 100)->nullable()->comment('Immune, Cardiovascular, Digestive...');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fl_health_conditions');
    }
};
