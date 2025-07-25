<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Admin\TestIberiaPdfParserController;

class TestIberiaPdfParserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:iberia-parser {--save : Whether to save the parsed data to DB (default: false)} {--pdf-path= : Custom PDF path in storage (overrides config)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the Iberia PDF parser controller method from CLI';

    /**
     * The controller instance.
     *
     * @var TestPdfParserController
     */
    protected $controller;

    /**
     * Create a new command instance.
     *
     * @param TestPdfParserController $controller
     */
    public function __construct(TestIberiaPdfParserController $controller)
    {
        parent::__construct();
        $this->controller = $controller;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Simula Request con params
        $params = ['save' => $this->option('save') ? true : false];

        if ($this->option('pdf-path')) {
            $params['pdf_path'] = $this->option('pdf-path'); // Si modificas controller para usarlo
            $this->info('Using custom PDF path: ' . $params['pdf_path']);
        }

        $request = Request::create('/test-iberia-pdf', 'GET', $params);

        try {
            $response = $this->controller->testIberiaPdfParse($request);

            $this->info('Response from controller:');
            $this->line(json_encode($response->getData(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return Command::SUCCESS;
        } catch (ValidationException $e) {
            $this->error('Validation error: ' . implode(', ', $e->errors()['save'] ?? []));
            return Command::FAILURE;
        } catch (\Throwable $e) {
            $this->error('Error executing test: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}