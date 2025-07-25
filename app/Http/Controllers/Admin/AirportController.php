<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reserva;
use App\Models\AirportReference;
use App\Models\IsoCountryCode; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AirportController extends Controller
{
    public function index(Request $request)
    {
        $query = Reserva::query();
        
        if ($request->filled('tipo')) {
            $query->where('tipo_reserva', $request->tipo);
        }
        if ($request->filled('email')) {
            $query->where('email_origen', $request->email);
        }
        if ($request->filled('pasajero_id')) { // Asumiendo que filtrarás por ID de pasajero
            $query->where('pasajero_id', $request->pasajero_id);
        } elseif ($request->filled('pasajero_nombre')) { // O por nombre a través de la relación
            $query->whereHas('pasajero', function ($q) use ($request) {
                $q->where('nombre_unificado', 'like', '%' . $request->pasajero_nombre . '%');
            });
        }
        if ($request->filled('fecha_desde')) {
            $query->where('fecha_inicio', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            // Si es fecha_fin de la reserva, el campo es fecha_fin. 
            // Si es hasta qué fecha de inicio buscar, sigue siendo fecha_inicio.
            $query->where('fecha_inicio', '<=', $request->fecha_hasta); 
        }
        if ($request->filled('ciudad')) {
            $query->where('ciudad', 'like', '%' . $request->ciudad . '%');
        }
        if ($request->filled('pais')) {
            $query->where('pais', 'like', '%' . $request->pais . '%');
        }
        
        // Eager load pasajero para mostrar nombre si es necesario
        $reservas = $query->with('pasajero')->orderBy('fecha_inicio', 'desc')
                                  ->paginate($request->input('per_page', 20)); // Paginación configurable
        
        return response()->json([
            'data' => $reservas->items(),
            'pagination' => [
                'current_page' => $reservas->currentPage(),
                'last_page' => $reservas->lastPage(),
                'per_page' => $reservas->perPage(),
                'total' => $reservas->total(),
                'from' => $reservas->firstItem(),
                'to' => $reservas->lastItem(),
            ]
        ]);
    }
    
    public function viajes()
    {
        // Emails para el filtro
        $emails = config('services.gemini.accounts', []); 

        // Tipos de reserva únicos
        $tiposReserva = Reserva::distinct()
            ->pluck('tipo_reserva')
            ->filter()
            ->sort()
            ->values();

        // Verificar última ejecución
        $archivo = Storage::disk('local')->path('ultima_ejecucion_vuelos.txt');
        $ultima = File::exists($archivo) ? Carbon::parse(File::get($archivo)) : null;
        $ahora = now();
        $actualizado = false;

        if (!$ultima || $ultima->diffInMinutes($ahora) > 180) {
            Artisan::call('vuelos:extraer', ['--no-gemini' => true]);
            Log::info("🛫 Se ejecutó vuelos:extraer automáticamente al cargar dashboard");
            $actualizado = true;
        } else {
            Log::info("⏳ Sincronización de vuelos omitida, última ejecución: {$ultima}");
        }

        return view('admin.stays.index', compact('emails', 'tiposReserva', 'actualizado'));
    }
    
    public function show($id)
    {
        $reserva = Reserva::with('pasajero')->findOrFail($id);
        return response()->json($reserva);
    }
    
    public function timeline() // Para FullCalendar o similar
    {
        $reservas = Reserva::whereNotNull('fecha_inicio')
                            ->orderBy('fecha_inicio')
                            ->get()
                            ->map(function($reserva) {
                                $title = ucfirst($reserva->tipo_reserva ?? 'Reserva');
                                if ($reserva->proveedor) $title .= " ({$reserva->proveedor})";
                                if ($reserva->ciudad) $title .= " - {$reserva->ciudad}";

                                $colorMap = [
                                    'vuelo' => '#3498db', 'hotel' => '#2ecc71', 'coche' => '#f1c40f',
                                    'tren' => '#e67e22', 'vivienda' => '#9b59b6', 'desconocido' => '#95a5a6'
                                ];

                                return [
                                    'id' => $reserva->id,
                                    'title' => $title,
                                    'start' => $reserva->fecha_inicio->format('Y-m-d'), // FullCalendar espera este formato
                                    'end' => $reserva->fecha_fin ? $reserva->fecha_fin->addDay()->format('Y-m-d') : null, // Para eventos de día completo, el final es exclusivo
                                    'allDay' => true, // Asumir que son eventos de día completo
                                    'className' => 'reserva-tipo-' . Str::slug($reserva->tipo_reserva ?? 'desconocido'),
                                    'color' => $colorMap[$reserva->tipo_reserva] ?? $colorMap['desconocido'],
                                    'extendedProps' => [ // Datos adicionales para mostrar en popups o modales
                                        'tipo' => ucfirst($reserva->tipo_reserva),
                                        'proveedor' => $reserva->proveedor,
                                        'ciudad' => $reserva->ciudad,
                                        'pais' => $reserva->pais,
                                        'numero_reserva' => $reserva->numero_reserva,
                                        'precio' => $reserva->precio,
                                        'moneda' => $reserva->moneda,
                                        'pasajero' => $reserva->pasajero ? $reserva->pasajero->nombre_unificado : 'N/A',
                                    ]
                                ];
                            });
        
        return response()->json($reservas);
    }
    
    public function exportar(Request $request) // CSV Export
    {
        // Reutilizar la lógica de filtrado de index()
        $query = Reserva::query();
        if ($request->filled('tipo')) $query->where('tipo_reserva', $request->tipo);
        if ($request->filled('fecha_desde')) $query->where('fecha_inicio', '>=', $request->fecha_desde);
        if ($request->filled('fecha_hasta')) $query->where('fecha_inicio', '<=', $request->fecha_hasta);
        // Añadir más filtros si es necesario

        $reservas = $query->with('pasajero')->orderBy('fecha_inicio')->get();
        
        if ($reservas->isEmpty()){
            return response("No hay reservas para exportar con los filtros aplicados.", 404);
        }

        $fileName = 'reservas_' . date('Y-m-d_H-i-s') . '.csv';
        $headers = [
            "Content-type"        => "text/csv; charset=utf-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($reservas) {
            $file = fopen('php://output', 'w');
            // BOM para UTF-8 en Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); 
            
            // Encabezados del CSV
            fputcsv($file, ['ID', 'Tipo Reserva', 'Proveedor', 'Nº Reserva', 'Fecha Inicio', 'Fecha Fin', 
                           'Ciudad', 'País', 'Dirección', 'Precio', 'Moneda', 
                           'Pasajero (Nombre Unificado)', 'Email Origen', 'Mensaje ID', 'Datos Adicionales']);

            foreach ($reservas as $reserva) {
                fputcsv($file, [
                    $reserva->id,
                    $reserva->tipo_reserva,
                    $reserva->proveedor ?? '',
                    $reserva->numero_reserva ?? '',
                    $reserva->fecha_inicio ? $reserva->fecha_inicio->format('Y-m-d') : '',
                    $reserva->fecha_fin ? $reserva->fecha_fin->format('Y-m-d') : '',
                    $reserva->ciudad ?? '',
                    $reserva->pais ?? '',
                    $reserva->direccion ?? '',
                    $reserva->precio ?? '',
                    $reserva->moneda ?? '',
                    $reserva->pasajero ? $reserva->pasajero->nombre_unificado : '',
                    $reserva->email_origen,
                    $reserva->mensaje_id,
                    json_encode($reserva->datos_adicionales) // Convertir array a JSON string para CSV
                ]);
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }

    protected function getCountryCodeMapArray(): array // Sin cambios
    {
        Log::info("Cargando mapeo de códigos de país desde la tabla local 't_iso2'.");
        $countryCodeMap = [];
        try {
            $countryCodeMap = IsoCountryCode::pluck('pais', 'iso2')->all();
            Log::info("Mapeo de códigos de país cargado con " . count($countryCodeMap) . " entradas desde 't_iso2'.");
        } catch (\Exception $e) {
            Log::error("Error al cargar mapeo de códigos de país desde 't_iso2': " . $e->getMessage());
        }
        if (empty($countryCodeMap)) {
            Log::warning("El mapa de códigos de país está vacío.");
        }
        return $countryCodeMap;
    }

    public function updateAirportReferenceData(Request $request) // Sin cambios mayores, ya era robusto
    {
        $csvDiskPath = 'data/airports.csv'; 
        $dataDir = 'data'; 
        $downloadUrl = 'https://davidmegginson.github.io/ourairports-data/airports.csv';
        $maxFileAgeInMonths = $request->input('max_file_age_months', 3); // Permite override

        Log::info("Iniciando proceso de actualización de referencias de aeropuertos.");

        if (!Storage::disk('local')->exists($dataDir)) {
            Storage::disk('local')->makeDirectory($dataDir);
        }

        $shouldDownloadAirportCsv = false;
        $airportCsvFileExistsInitially = Storage::disk('local')->exists($csvDiskPath);

        if (!$airportCsvFileExistsInitially) {
            Log::info("Archivo de aeropuertos {$csvDiskPath} no existe. Se intentará descargar.");
            $shouldDownloadAirportCsv = true;
        } else {
            // ... (lógica de chequeo de antigüedad del archivo como en tu original)
             try {
                $lastModifiedTimestamp = Storage::disk('local')->lastModified($csvDiskPath);
                $fileLastModifiedDate = Carbon::createFromTimestamp($lastModifiedTimestamp);
                $cutoffDate = Carbon::now()->subMonths($maxFileAgeInMonths);

                if ($fileLastModifiedDate->lt($cutoffDate)) {
                    Log::info("Archivo de aeropuertos {$csvDiskPath} es más antiguo de {$maxFileAgeInMonths} meses. Se intentará actualizar.");
                    $shouldDownloadAirportCsv = true;
                } else {
                    Log::info("Archivo de aeropuertos {$csvDiskPath} está actualizado.");
                }
            } catch (\Exception $e) {
                Log::error("Error al verificar fecha de mod. de {$csvDiskPath}: " . $e->getMessage() . ". Se intentará descargar.");
                $shouldDownloadAirportCsv = true;
            }
        }

        if ($shouldDownloadAirportCsv || $request->has('force_download')) { // Opción para forzar descarga
            Log::info("Intentando descargar {$downloadUrl} a {$csvDiskPath}...");
            // ... (lógica de descarga HTTP como en tu original) ...
            try {
                $response = Http::timeout(180)->get($downloadUrl); // Aumentar timeout
                if ($response->successful()) {
                    if (Storage::disk('local')->put($csvDiskPath, $response->body())) {
                        Log::info("Archivo {$csvDiskPath} descargado/actualizado y guardado exitosamente.");
                    } else { /* ... log error guardado ... */ }
                } else { /* ... log error descarga ... */ }
            } catch (\Exception $e) { /* ... log excepción descarga ... */ }
        }
        
        if (!Storage::disk('local')->exists($csvDiskPath)) { /* ... log error crítico y return ... */ }
        
        Log::info("Confirmado: Archivo de aeropuertos {$csvDiskPath} existe. Procediendo a procesar...");
        $countryCodeToName = $this->getCountryCodeMapArray(); // Usa t_iso2
        if (empty($countryCodeToName)) Log::warning("Mapa de códigos de país (t_iso2) está vacío.");

        $processedCount = 0; $updatedCount = 0; $createdCount = 0;
        $startTime = microtime(true);

        DB::beginTransaction();
        try {
            // ... (resto de la lógica de procesar el CSV como en tu original, usando $countryCodeToName)
            // ... Asegúrate que la lógica de updateOrCreate para AirportReference sea correcta
            $absoluteCsvPath = Storage::disk('local')->path($csvDiskPath);
            if (!file_exists($absoluteCsvPath) || !is_readable($absoluteCsvPath)) {
                 throw new \Exception("Archivo CSV no existe o no es legible en: " . $absoluteCsvPath);
            }
            $header = null; $columnIndices = [];
            if (($handle = fopen($absoluteCsvPath, 'r')) !== false) {
                while (($row = fgetcsv($handle)) !== false) {
                    if (!$header) {
                        // ... (obtener índices de columnas: iata_code, municipality, iso_country, type, scheduled_service, name, ident)
                        $header = array_map('trim', $row);
                        $requiredColumns = ['iata_code', 'municipality', 'iso_country', 'type', 'scheduled_service', 'name', 'ident'];
                        // ... (validar que todas las columnas existan)
                        foreach ($requiredColumns as $colName) {
                            $index = array_search($colName, $header);
                            if ($index === false && $colName !== 'iata_code') { // iata_code puede ser vacío pero otras son más importantes
                                // Considera si 'iata_code' puede ser opcional si 'ident' (ICAO) existe
                            }
                            if ($index === false && $colName === 'iata_code') {
                                //Log::debug("Columna 'iata_code' no encontrada, pero podría ser opcional si 'ident' (ICAO) existe.");
                            } elseif ($index === false) {
                                 throw new \Exception("Columna requerida '{$colName}' no encontrada en airports.csv. Encabezados: " . implode(', ', $header));
                            }
                            $columnIndices[$colName] = $index;
                        }
                        continue;
                    }
                    
                    // Mapeo cuidadoso de columnas
                    $iataCode = isset($columnIndices['iata_code'], $row[$columnIndices['iata_code']]) ? trim($row[$columnIndices['iata_code']]) : null;
                    $icaoCode = isset($columnIndices['ident'], $row[$columnIndices['ident']]) ? trim($row[$columnIndices['ident']]) : null; // Código ICAO
                    $airportName = isset($columnIndices['name'], $row[$columnIndices['name']]) ? trim($row[$columnIndices['name']]) : null;
                    $municipality = isset($columnIndices['municipality'], $row[$columnIndices['municipality']]) ? trim($row[$columnIndices['municipality']]) : null;
                    $isoCountryFromAirport = isset($columnIndices['iso_country'], $row[$columnIndices['iso_country']]) ? strtoupper(trim($row[$columnIndices['iso_country']])) : null;
                    $airportType = isset($columnIndices['type'], $row[$columnIndices['type']]) ? trim($row[$columnIndices['type']]) : null;
                    $hasScheduledService = isset($columnIndices['scheduled_service'], $row[$columnIndices['scheduled_service']]) ? (trim($row[$columnIndices['scheduled_service']]) === 'yes') : false;

                    // Solo procesar aeropuertos relevantes
                    if ($hasScheduledService && in_array($airportType, ['medium_airport', 'large_airport', 'seaplane_base', 'heliport'])) {
                        if (empty($isoCountryFromAirport)) continue; // Necesitamos el país

                        $countryName = $countryCodeToName[$isoCountryFromAirport] ?? $isoCountryFromAirport; // Fallback al código ISO si no hay mapeo

                        // Guardar por IATA si existe
                        if (!empty($iataCode)) {
                            $ref = AirportReference::updateOrCreate(
                                ['identifier_type' => 'iata', 'identifier_value' => strtoupper($iataCode)],
                                ['country_name' => $countryName, 'airport_name' => $airportName, 'municipality' => $municipality, 'iso_country_code' => $isoCountryFromAirport]
                            );
                            $ref->wasRecentlyCreated ? $createdCount++ : $updatedCount++;
                            $processedCount++;
                        }
                        // Guardar por Ciudad (municipality) si existe
                        if (!empty($municipality)) {
                            $normalizedMunicipality = Str::ascii(strtolower($municipality)); // Normalizar
                            if(!empty($normalizedMunicipality)) {
                                $ref = AirportReference::updateOrCreate(
                                    ['identifier_type' => 'city', 'identifier_value' => $normalizedMunicipality],
                                    ['country_name' => $countryName, 'iso_country_code' => $isoCountryFromAirport] // Para ciudad, el país es lo más importante
                                );
                                 // No contamos city como processed separate si ya hubo iata para el mismo aeropuerto, o sí?
                                 // Esto es para búsquedas por nombre de ciudad.
                            }
                        }
                        // Podrías guardar por ICAO también si lo necesitas: AirportReference::updateOrCreate(['identifier_type' => 'icao', ...])
                    }
                }
                fclose($handle);
            } else { /* ... log error fopen ... */ }

            DB::commit();
            $endTime = microtime(true);
            Log::info("Actualización de AirportReference completada. Procesados (IATA): {$processedCount} (Creados: {$createdCount}, Actualizados: {$updatedCount}). Tiempo: " . round($endTime - $startTime, 2) . "s");
            return response()->json([
                'message' => 'Referencias de aeropuertos actualizadas.',
                'iata_records_processed' => $processedCount,
                'iata_created' => $createdCount,
                'iata_updated' => $updatedCount,
            ]);

        } catch (\Exception $e) {
            DB::rollBack(); /* ... log error y return JSON error ... */ 
             return response()->json(['message' => 'Error procesando CSV: ' . $e->getMessage()], 500);
        }
    }

    public function showAirportAdminToolPage() // Sin cambios
    {
        return view('admin.airports.index');
    }

    public function getCountryFromIdentifier(string $identifier): ?string // Sin cambios
    {
        $identifier = trim($identifier); $countryReference = null;
        if (strlen($identifier) === 3 && ctype_upper($identifier)) { // Asumir IATA
            $countryReference = AirportReference::where('identifier_type', 'iata')->where('identifier_value', $identifier)->first();
        }
        if (!$countryReference) { // Si no es IATA o no se encontró, buscar como ciudad
            $normalizedIdentifier = Str::ascii(strtolower($identifier));
            $countryReference = AirportReference::where('identifier_type', 'city')->where('identifier_value', $normalizedIdentifier)->first();
        }
        return $countryReference ? $countryReference->country_name : null;
    }

    public function testGetCountry(Request $request) // Sin cambios
    {
        $identifier = $request->input('identifier', 'BOG');
        $country = $this->getCountryFromIdentifier($identifier);
        if ($country) return response()->json(['identifier' => $identifier, 'country' => $country]);
        else return response()->json(['identifier' => $identifier, 'message' => 'País no encontrado'], 404);
    }

}