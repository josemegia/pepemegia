<?php

namespace App\Console\Commands;

use App\Models\FlApiClient;
use Illuminate\Console\Command;

class ApiClientList extends Command
{
    protected $signature = 'api:client-list';
    protected $description = 'Listar todos los API clients';

    public function handle()
    {
        $clients = FlApiClient::all();

        if ($clients->isEmpty()) {
            $this->info('No hay API clients registrados.');
            return;
        }

        $this->table(
            ['ID', 'Nombre', 'Plan', 'Hoy', 'Mes', 'Dominio', 'Activo'],
            $clients->map(fn($c) => [
                $c->id,
                $c->name,
                $c->plan,
                $c->requests_today . '/' . $c->daily_limit,
                $c->requests_this_month . '/' . $c->monthly_limit,
                $c->domain ?? '-',
                $c->is_active ? 'Sí' : 'No',
            ])
        );
    }
}
