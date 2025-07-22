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
            // Campos para Socialite (se añaden solo si no existen)
            if (!Schema::hasColumn('users', 'social_provider_name')) {
                $table->string('social_provider_name')->nullable()->after('password'); // ej. 'google', 'microsoft'
            }
            if (!Schema::hasColumn('users', 'social_provider_id')) {
                $table->string('social_provider_id')->nullable()->after('social_provider_name'); // ID del usuario en el proveedor
            }
            if (!Schema::hasColumn('users', 'social_provider_token')) {
                $table->text('social_provider_token')->nullable()->after('social_provider_id'); // Token de acceso
            }
            if (!Schema::hasColumn('users', 'social_provider_refresh_token')) {
                $table->text('social_provider_refresh_token')->nullable()->after('social_provider_token'); // Refresh token (si está disponible)
            }

            // Campos para el perfil extendido (se añaden solo si no existen)
            if (!Schema::hasColumn('users', 'profile_photo_path')) {
                $table->string('profile_photo_path', 2048)->nullable()->after('remember_token'); // Ruta de la foto de perfil
            }
            if (!Schema::hasColumn('users', 'address')) {
                $table->string('address')->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'city')) {
                $table->string('city')->nullable()->after('address');
            }
            if (!Schema::hasColumn('users', 'country')) {
                $table->string('country')->nullable()->after('city');
            }
            if (!Schema::hasColumn('users', 'phone_number')) {
                $table->string('phone_number')->nullable()->after('country');
            }

            // Asegúrate de eliminar xrp_address si existía de una migración anterior
            // if (Schema::hasColumn('users', 'xrp_address')) {
            //     $table->dropColumn('xrp_address');
            // }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Se eliminan solo si existen para evitar errores al revertir
            if (Schema::hasColumn('users', 'social_provider_name')) {
                $table->dropColumn('social_provider_name');
            }
            if (Schema::hasColumn('users', 'social_provider_id')) {
                $table->dropColumn('social_provider_id');
            }
            if (Schema::hasColumn('users', 'social_provider_token')) {
                $table->dropColumn('social_provider_token');
            }
            if (Schema::hasColumn('users', 'social_provider_refresh_token')) {
                $table->dropColumn('social_provider_refresh_token');
            }
            if (Schema::hasColumn('users', 'profile_photo_path')) {
                $table->dropColumn('profile_photo_path');
            }
            if (Schema::hasColumn('users', 'address')) {
                $table->dropColumn('address');
            }
            if (Schema::hasColumn('users', 'city')) {
                $table->dropColumn('city');
            }
            if (Schema::hasColumn('users', 'country')) {
                $table->dropColumn('country');
            }
            if (Schema::hasColumn('users', 'phone_number')) {
                $table->dropColumn('phone_number');
            }

            // Si eliminaste xrp_address en up(), considera añadirlo de nuevo en down() si es necesario
            // $table->string('xrp_address')->nullable()->unique();
        });
    }
};