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
        Schema::create('recaptcha_blocked_ips', function (Blueprint $table) {
            $table->id();
            $table->string('ip')->unique();
            $table->unsignedInteger('attempts')->default(1);
            $table->json('metadata')->nullable(); // info de IP como ASN, proveedor, etc.
            $table->timestamp('last_attempt_at');
            $table->timestamp('blocked_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recaptcha_blocked_ips');
    }
};