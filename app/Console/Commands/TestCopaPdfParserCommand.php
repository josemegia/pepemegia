<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Admin\TestCopaPdfParserController;

class TestCopaPdfParserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:copa-ticket 
        {--save : Guarda los datos en la base de datos} 
        {--pdf-path= : Ruta personalizada del PDF (relativa a storage/app/)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parsea un PDF de Copa Airlines y muestra el resultado por consola.';

    /**
     * Ejecuta el comando.
     *
     * @return int
     */
    public function handle()
    {
        // Configuración del path del PDF
        if ($this->option('pdf-path')) {
            config(['app.pdf_test_path_copa' => $this->option('pdf-path')]);
            $this->info('➡ Usando PDF personalizado: ' . $this->option('pdf-path'));
        } else {
            $this->info('➡ Usando PDF por defecto: ' . config('app.pdf_ticket_path_copa', 'data/copaairline.pdf'));
        }

        // Crear request simulada con parámetro "save"
        $params = ['save' => $this->option('save') ? true : false];
        $request = Request::create('/test-copa-pdf', 'GET', $params);

        // Registrar request en el contenedor
        Container::getInstance()->instance('request', $request);
        Facade::clearResolvedInstance('request');

        try {
            // Ejecutar el controlador usando app()->call para evitar errores de inyección
            $controller = app(TestCopaPdfParserController::class);
            $response = app()->call([$controller, 'testCopaPdfParse'], [
                'request' => $request,
            ]);

            // Mostrar resultado
            $this->line("\n📋 Resultado del parser:\n");
            $this->line($response->getContent());

            return Command::SUCCESS;
        } catch (ValidationException $e) {
            $this->error('❌ Error de validación: ' . implode(', ', $e->errors()['save'] ?? []));
            return Command::FAILURE;
        } catch (\Throwable $e) {
            $this->error('❌ Excepción: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
