<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fl_scrape_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained('fl_countries')->cascadeOnDelete();
            $table->enum('method', ['curl', 'puppeteer', 'manual']);
            $table->enum('status', ['success', 'failed', 'partial']);
            $table->string('url', 500);
            $table->unsignedInteger('products_found')->default(0);
            $table->unsignedInteger('products_created')->default(0);
            $table->unsignedInteger('products_updated')->default(0);
            $table->unsignedInteger('products_missing')->default(0);
            $table->unsignedInteger('duration_ms')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->timestamps();

            $table->index(['country_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fl_scrape_logs');
    }
};
