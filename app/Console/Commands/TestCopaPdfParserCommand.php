<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Parsers\Airlines\CopaParser;
use App\Services\ReservationRegistrar;
use App\Models\Pasajero;
use Throwable;

class TestCopaPdfParserCommand extends Command
{
    /**
     * Modificamos la firma para añadir las opciones --save y --dry-run
     */
    protected $signature = 'test:copa-parser
                            {--pdf-path= : Ruta del PDF dentro de storage/app/}
                            {--save : Guarda los datos parseados en la base de datos}
                            {--dry-run : Muestra los datos que se guardarían sin afectar la BD}';

    protected $description = 'Testea el parser CopaParser. Puede mostrar, simular el guardado (dry-run) o guardar los datos en la BD.';

    /**
     * Inyectamos las dependencias que necesitamos en el método handle.
     */
    public function handle(CopaParser $parser, ReservationRegistrar $registrar): int
    {
        $pdfPath = $this->option('pdf-path') ?? config('app.pdf_pase_path_copa', 'data/BoardingPass.pdf');

        if (!Storage::disk('local')->exists($pdfPath)) {
            $this->error("❌ No se encontró el archivo: {$pdfPath}");
            return Command::FAILURE;
        }

        $fullPath = Storage::disk('local')->path($pdfPath);
        $this->info("📄 Cargando PDF desde: {$fullPath}");

        try {
            $parsedData = $parser->parseFile($fullPath);

            if (!$parsedData) {
                $this->warn("⚠️ No se pudo parsear el archivo o está vacío.");
                return Command::FAILURE;
            }
            
            $parsedList = [];
            if (isset($parsedData['reserva_data'])) {
                $parsedList[] = $parsedData;
            } elseif (is_array($parsedData) && isset($parsedData[0]['reserva_data'])) {
                $parsedList = $parsedData;
            } else {
                 $this->warn("⚠️ El formato de los datos parseados no es el esperado.");
                 return Command::FAILURE;
            }

            if ($this->option('dry-run')) {
                 $this->info("\033[33m🧪 Modo DRY-RUN activado: Mostrando datos extraídos sin guardar.\033[0m");
                 dump($parsedList);
                 return Command::SUCCESS;
            }

            if ($this->option('save')) {
                $this->info("💾 Opción --save detectada. Procesando " . count($parsedList) . " reserva(s)...");

                foreach ($parsedList as $parsedItem) {
                    $this->info("\033[36m🔄 Procesando reserva...\033[0m");

                    $pasajeroData = $parsedItem['pasajero_data'];
                    $reservaData = $parsedItem['reserva_data'];
                    $segmentos = $reservaData['datos_adicionales']['segmentos_vuelo'] ?? [];

                    if (empty($pasajeroData) || empty($reservaData)) {
                        $this->warn("⚠️ Elemento de reserva inválido. Saltando...");
                        continue;
                    }
                    
                    // --- INICIO DE LA NUEVA LÓGICA DE BÚSQUEDA ---
                    $nombreOriginal = $pasajeroData['nombre_original'] ?? null;
                    $nombreUnificado = $pasajeroData['nombre_unificado'] ?? null;

                    // 1. Buscar pasajero por variantes en la columna JSON
                    $pasajero = Pasajero::whereJsonContains('variantes', $nombreOriginal)
                                        ->orWhereJsonContains('variantes', $nombreUnificado)
                                        ->first();

                    // 2. Si no se encuentra, crear uno nuevo
                    if (!$pasajero) {
                        $this->info("Pasajero no encontrado para '{$nombreOriginal}'. Creando nuevo registro...");
                        
                        // Generar variantes iniciales. En un futuro, un servicio podría generar más.
                        $initialVariants = array_values(array_unique(array_filter([
                            $nombreOriginal,
                            $nombreUnificado
                        ])));

                        $pasajero = Pasajero::create([
                            'nombre_original' => $nombreOriginal,
                            'nombre_unificado' => $nombreUnificado,
                            'variantes' => $initialVariants,
                        ]);
                    } else {
                        $this->info("Pasajero '{$pasajero->nombre_original}' encontrado en la BD.");
                        // Opcional: Si quieres enriquecer las variantes del pasajero existente
                        $newVariants = array_values(array_unique(array_merge($pasajero->variantes, [$nombreOriginal, $nombreUnificado])));
                        $pasajero->update(['variantes' => $newVariants]);
                    }
                    // --- FIN DE LA NUEVA LÓGICA DE BÚSQUEDA ---

                    $registrar->guardar(
                        $reservaData,
                        $segmentos,
                        $pasajero,
                        'importacion_desde_consola',
                        null,
                        "Importado desde el archivo: " . basename($pdfPath)
                    );

                    $this->info("✅ ¡Reserva para {$pasajero->nombre_original} guardada exitosamente!");
                }

            } else {
                $this->info("✅ Parseo exitoso (sin guardar):");
                $this->line(json_encode($parsedList, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }

            return Command::SUCCESS;

        } catch (Throwable $e) {
            Log::error('Error en test:copa-pase', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $this->error('❌ Excepción: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
