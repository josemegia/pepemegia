<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fl_api_clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('api_key', 64)->unique();
            $table->string('api_secret', 64);
            $table->string('domain')->nullable();
            $table->enum('plan', ['free', 'pro', 'enterprise'])->default('free');
            $table->integer('rate_limit_per_minute')->default(10);
            $table->integer('daily_limit')->default(100);
            $table->integer('monthly_limit')->default(1000);
            $table->integer('requests_today')->default(0);
            $table->integer('requests_this_month')->default(0);
            $table->timestamp('last_request_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('allowed_endpoints')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fl_api_clients');
    }
};
