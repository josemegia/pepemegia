<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fl_consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained('fl_countries')->cascadeOnDelete();
            $table->string('session_id', 100)->index();
            $table->string('ip_hash', 64);
            $table->enum('input_type', ['text', 'image', 'pdf'])->default('text');
            $table->text('input_text')->nullable();
            $table->string('input_file_path', 500)->nullable();
            $table->json('detected_conditions')->nullable();
            $table->json('products_sent')->nullable();
            $table->string('ai_model', 50);
            $table->longText('ai_response')->nullable();
            $table->unsignedInteger('ai_tokens_input')->nullable();
            $table->unsignedInteger('ai_tokens_output')->nullable();
            $table->decimal('ai_cost_usd', 8, 6)->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->unsignedTinyInteger('user_rating')->nullable();
            $table->timestamps();

            $table->index(['country_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fl_consultations');
    }
};
