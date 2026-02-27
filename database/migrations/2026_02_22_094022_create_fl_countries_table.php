<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fl_countries', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique()->comment('Subdominio 4Life: costarica, colombia, us');
            $table->string('name', 100);
            $table->char('iso_code', 2)->index()->comment('ISO 3166-1: CR, CO, US — lo que devuelve GeoIP');
            $table->string('shop_url', 255)->comment('https://costarica.4life.com/corp/shop/all');
            $table->char('currency_code', 3)->comment('CRC, COP, USD, EUR');
            $table->string('currency_symbol', 5)->default('$');
            $table->string('locale', 10)->default('es')->comment('es_CR, es_CO, en_US — para Accept-Language del scraper');
            $table->boolean('is_active')->default(false);
            $table->enum('scrape_method', ['curl', 'puppeteer', 'manual', 'none'])->default('none');
            $table->enum('scrape_status', ['ok', 'failed', 'pending', 'never'])->default('never');
            $table->string('scrape_days', 50)->default('1,21')->comment('Días del mes para scrapear');
            $table->timestamp('last_scraped_at')->nullable();
            $table->text('last_error')->nullable();
            $table->unsignedInteger('products_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fl_countries');
    }
};
