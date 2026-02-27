<?php

namespace App\Console\Commands;

use App\Models\FlApiClient;
use Illuminate\Console\Command;

class ApiClientCreate extends Command
{
    protected $signature = 'api:client-create
        {name : Nombre del cliente}
        {email : Email de contacto}
        {--domain= : Dominio autorizado}
        {--plan=free : Plan (free/pro/enterprise)}';

    protected $description = 'Crear un nuevo API client';

    public function handle()
    {
        $credentials = FlApiClient::generateCredentials();

        $limits = match($this->option('plan')) {
            'pro' => ['rate' => 30, 'daily' => 500, 'monthly' => 10000],
            'enterprise' => ['rate' => 100, 'daily' => 5000, 'monthly' => 100000],
            default => ['rate' => 10, 'daily' => 100, 'monthly' => 1000],
        };

        $client = FlApiClient::create([
            'name' => $this->argument('name'),
            'email' => $this->argument('email'),
            'api_key' => $credentials['api_key'],
            'api_secret' => $credentials['api_secret'],
            'domain' => $this->option('domain'),
            'plan' => $this->option('plan'),
            'rate_limit_per_minute' => $limits['rate'],
            'daily_limit' => $limits['daily'],
            'monthly_limit' => $limits['monthly'],
        ]);

        $this->info("\n✅ API Client creado exitosamente!");
        $this->table(
            ['Campo', 'Valor'],
            [
                ['Nombre', $client->name],
                ['Email', $client->email],
                ['API Key', $client->api_key],
                ['Plan', $client->plan],
                ['Límite diario', $limits['daily']],
                ['Límite mensual', $limits['monthly']],
            ]
        );

        $this->warn("\n⚠️  Guarda la API Key, no se mostrará de nuevo.");
    }
}
