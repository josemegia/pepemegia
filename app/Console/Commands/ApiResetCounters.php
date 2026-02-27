<?php

namespace App\Console\Commands;

use App\Models\FlApiClient;
use Illuminate\Console\Command;

class ApiResetCounters extends Command
{
    protected $signature = 'api:reset-counters {--monthly}';
    protected $description = 'Resetear contadores de uso de la API';

    public function handle()
    {
        FlApiClient::query()->update(['requests_today' => 0]);
        $this->info('Contadores diarios reseteados.');

        if ($this->option('monthly')) {
            FlApiClient::query()->update(['requests_this_month' => 0]);
            $this->info('Contadores mensuales reseteados.');
        }
    }
}
