<?php

namespace App\Console\Commands;

use Google\Client as GoogleClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GmailAuthCommand extends Command
{
    protected $signature = 'gmail:auth {email : Dirección de correo para autenticar}';
    protected $description = 'Genera token OAuth2 para Gmail y lo guarda en storage/app/private/google/token-EMAIL.json';

    public function handle()
    {
        $email = $this->argument('email');
        $credentialsPath = storage_path('app/private/google/credentials.json');
        $tokenPath = storage_path("app/private/google/token-{$email}.json");

        if (!file_exists($credentialsPath)) {
            $this->error("❌ No se encontró el archivo de credenciales en: {$credentialsPath}");
            return Command::FAILURE;
        }

        $client = new GoogleClient();
        $client->setAuthConfig($credentialsPath);
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->setScopes([
            'https://www.googleapis.com/auth/gmail.readonly',
            'https://www.googleapis.com/auth/gmail.modify',
            'https://www.googleapis.com/auth/gmail.labels',
            'https://www.googleapis.com/auth/gmail.metadata',
        ]);
        $client->setRedirectUri('urn:ietf:wg:oauth:2.0:oob');

        $authUrl = $client->createAuthUrl();
        $this->info("🔗 Abre esta URL en tu navegador para autorizar la cuenta:");
        $this->line($authUrl);

        $code = $this->ask('🔐 Pega aquí el código de autorización');
        $accessToken = $client->fetchAccessTokenWithAuthCode($code);

        if (isset($accessToken['error'])) {
            $this->error("❌ Error al obtener el token: " . $accessToken['error_description']);
            return Command::FAILURE;
        }

        if (!is_dir(dirname($tokenPath))) {
            mkdir(dirname($tokenPath), 0775, true);
        }

        file_put_contents($tokenPath, json_encode($accessToken, JSON_PRETTY_PRINT));
        $this->info("✅ Token guardado en: {$tokenPath}");

        return Command::SUCCESS;
    }
}
