<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fl_api_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_client_id')->constrained('fl_api_clients')->onDelete('cascade');
            $table->string('endpoint');
            $table->string('method', 10);
            $table->integer('status_code');
            $table->integer('response_time_ms');
            $table->string('ip_address', 45);
            $table->json('request_params')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['api_client_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fl_api_logs');
    }
};
