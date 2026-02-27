<?php
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// API: resetear contadores
Schedule::command('api:reset-counters')->dailyAt('00:00');
Schedule::command('api:reset-counters --monthly')->monthlyOn(1, '00:05');
