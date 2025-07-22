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
        Schema::table('users', function (Blueprint $table) {
            $table->string('social_provider_name')->nullable()->after('password'); // ej. 'google', 'microsoft'
            $table->string('social_provider_id')->nullable()->after('social_provider_name'); // ID del usuario en el proveedor
            $table->text('social_provider_token')->nullable()->after('social_provider_id'); // Token de acceso
            $table->text('social_provider_refresh_token')->nullable()->after('social_provider_token'); // Refresh token (si está disponible)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['social_provider_name', 'social_provider_id', 'social_provider_token', 'social_provider_refresh_token']);
        });
    }
};