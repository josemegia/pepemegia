<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\{
    Auth,
    Session,
    Storage,
    File,
    Log
};
use libphonenumber\PhoneNumberUtil;
use Carbon\Carbon;

use App\Services\ShortUrlService;
use App\Services\PhoneNumberService;

use App\Jobs\FlyerJob;

use Symfony\Component\HttpFoundation\File\UploadedFile;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Imagick\Driver;


class FlyerController extends Controller
{
    protected ShortUrlService $shortener;
    protected PhoneNumberService $phoneService;

    public function __construct(ShortUrlService $shortener, PhoneNumberService $phoneService)
    {
        $this->shortener = $shortener;
        $this->phoneService = $phoneService;
    }

    /**
     * Muestra la página principal del flyer para el "administrador".
     * Permite la rotación de temas y carga el formato guardado o por defecto.
     */
    public function show(Request $request)
    {
        // 1. Lógica de tema: solo rotar si NO vienes de un guardado exitoso
        $flyerData = $this->loadFlyerData(); // primero cargamos los datos del JSON

        $themeNames = array_keys(config('flyer.themes'));
        $defaultTheme = $flyerData['theme'] ?? config('flyer.default_theme');

        // Si vienes de update() (has('success') o has('shared_link')), NO rotamos
        if (session()->has('success') || session()->has('shared_link')) {
            $activeThemeName = $defaultTheme;
        } else {
            // Rota el tema normalmente
            $lastThemeName = session('current_flyer_theme', $defaultTheme);
            $lastThemeIndex = array_search($lastThemeName, $themeNames);
            if ($lastThemeIndex === false) $lastThemeIndex = -1;
            $nextThemeIndex = ($lastThemeIndex + 1) % count($themeNames);
            $activeThemeName = $themeNames[$nextThemeIndex];
            session(['current_flyer_theme' => $activeThemeName]);
        }

        // Combinar con el tema por defecto
        $theme = array_merge(config('flyer.themes.default'), config("flyer.themes.{$activeThemeName}", []));

        // 2. Cargar Datos del Flyer (incluye el formato guardado)
        // Usa el método auxiliar loadFlyerData para obtener los datos persistentes.
        $flyerData = $this->loadFlyerData();

        // 3. Determinar el Formato Activo (SIN ROTACIÓN AUTOMÁTICA AQUÍ)
        // Prioridad:
        // a) Parámetro de consulta en la URL (ej. /flyer?format=minimalist) para previsualización.
        // b) Formato guardado en los datos del flyer ($flyerData['format']).
        // c) Formato por defecto del archivo de configuración (config('flyer.default_format')).
        if ($request->has('format')) {
            $activeFormatName = $request->query('format');
            session(['current_flyer_format' => $activeFormatName]);
        } else {
            $activeFormatName = session('current_flyer_format', $flyerData['format'] ?? config('flyer.default_format'));
        }

        // Asegurarse de que el formato final sea válido (en caso de que se borre del config)
        $availableFormats = array_keys(config('flyer.formats'));
        if (!in_array($activeFormatName, $availableFormats))
            $activeFormatName = config('flyer.default_format'); // Revertir a default si el formato no existe

        // Obtener la vista Blade asociada al formato activo
        $activeFormatView = config("flyer.formats.{$activeFormatName}.view");

        $response = response()->view($activeFormatView, [
            'theme' => $theme,
            'data' => $flyerData,
            'is_shared_view' => false,
            'current_format_name' => $activeFormatName,
            'available_formats' => config('flyer.formats'),
            'uuid' => $uuid ?? null,
            'filename' => $filename ?? null,
        ]);
        $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->header('Pragma', 'no-cache');
        $response->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
        return $response;
    }

    /**
     * Muestra un flyer guardado y compartible.
     * Carga el tema y el formato guardados en el JSON.
     */
    public function showShared($uuid, $filename)
    {
        $filePath = "flyers/shared/{$uuid}/{$filename}";

        if (!Storage::disk('public')->exists($filePath)) {
            abort(404, 'Flyer no encontrado.');
        }

        $jsonData = Storage::disk('public')->get($filePath);
        $data = json_decode($jsonData, true);

        $activeThemeName = $data['theme'] ?? config('flyer.default_theme');
        $theme = array_merge(
            config('flyer.themes.default'),
            config("flyer.themes.{$activeThemeName}", [])
        );

        $activeFormatName = $data['format'] ?? config('flyer.default_format');
        $activeFormatView = config("flyer.formats.{$activeFormatName}.view");

        return view($activeFormatView, [
            'theme' => $theme,
            'data' => $data,
            'is_shared_view' => true,
            'current_format_name' => $activeFormatName,
            'uuid' => $uuid ?? null,
            'filename' => $filename ?? null,
        ]);
    }

    /**
     * Muestra el formulario para editar/crear un flyer.
     * Carga los datos existentes y los formatos disponibles.
     */
    public function showForm()
    {
        $data = $this->loadFlyerData();
        $formats = config('flyer.formats');
        $phoneUtil = PhoneNumberUtil::getInstance();
        $regions = $phoneUtil->getSupportedRegions();
        $defaultregion = $data['event']['phone_country'] ?? config('app.iso2', 'CO');

        return view('flyer.flyer_form', [
            'data' => $data,
            'formats' => $formats,
            'theme' => '',
            'is_shared_view' => false,
            'uuid' => null,
            'filename' => null,
            'current_format_name' => null,
            'regions' => $regions,
            'defaultregion' => $defaultregion,
            'presetlinks' => config('flyer.links',[]),
        ]);
    }

    /**
     * Valida y guarda los datos del formulario en un archivo JSON.
     */

    public function update(Request $request)
    {
        Log::info('--- INICIO DE PROCESO DE SUBIDA DE FLYER ---');
        Log::info('Request data:', $request->all());
        Log::info('Has file "speaker_image"? ' . ($request->hasFile('speaker_image') ? 'Yes' : 'No'));
        
        if ($request->file('speaker_image')) {
            $uploadedFile = $request->file('speaker_image');
            Log::info('Uploaded file details:', [
                'originalName' => $uploadedFile->getClientOriginalName(),
                'mimeType' => $uploadedFile->getClientMimeType(),
                'size' => $uploadedFile->getSize(),
                'path' => $uploadedFile->getPathname(),
                'phpError' => $uploadedFile->getError(),
                'phpErrorMessage' => $uploadedFile->getErrorMessage(),
            ]);
            if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
                Log::error('PHP Upload Error Detected: Code ' . $uploadedFile->getError() . ' - ' . $uploadedFile->getErrorMessage());
            }
        } else {
             Log::info('No file "speaker_image" detected by Laravel. Check php.ini limits or form submission.');
        }

        $validatedData = $request->validate([
            'mainTitle' => 'required|string|max:255',
            'subtitle' => 'required|string|max:255',
            'speaker_name' => 'required|string|max:255',
            'speaker_title' => 'required|string|max:255',
            'speaker_quote' => 'required|string|max:255',
            'event_date' => 'required|string|max:255',
            'event_time' => 'required|string|max:255',
            'event_platform' => 'required|string|max:255',
            'event_platform_details' => 'required|string|max:255',
            'cta_link' => 'required|url',
            'speaker_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:30000',
            'flyer_format' => 'required|string|in:' . implode(',', array_keys(config('flyer.formats'))),
            'event_phone_country' => 'nullable|string|size:2',
            'event_phone' => 'nullable|string|max:20',
        ]);

        Log::info('Validation passed for speaker_image.');

        $currentFlyerData = $this->loadFlyerData();
        $imageName = $currentFlyerData['speaker']['image'] ?? config('flyer.speaker.image');
        
        $imageUploadFailedDueToPhpLimits = false;

        if ($request->hasFile('speaker_image')) {
            
            Log::info('Request has file, proceeding with storage logic.');

            // Asegurarse de que $uploadedFileInstance está definida y es el archivo subido
            $uploadedFileInstance = $request->file('speaker_image');
            // Esta será la ruta temporal donde PHP guarda la imagen covereada
            // y donde Python la lee, procesa (quitando fondo) y sobrescribe.
            $tempFilePathForProcessing = $uploadedFileInstance->getPathname();

            // Eliminar la imagen anterior si no es la por defecto
            // Asumo que $imageName contiene el nombre del archivo de la imagen anterior
            // que se quiere reemplazar (ej. de la base de datos).
            // Si es una nueva subida sin imagen anterior, $imageName debe ser null o no estar definida aquí.
            if (
                isset($imageName) && // Asegura que $imageName está definida antes de usarla
                $imageName &&
                $imageName !== config('flyer.speaker.image') &&
                Storage::disk('public')->exists('flyers/' . $imageName)
            ) {
                Storage::disk('public')->delete('flyers/' . $imageName);
                Log::info('Old image deleted: ' . $imageName);
            }

            // Generar nombre y ruta de la imagen nueva (final)
            // $imageName se usará como el nombre del archivo final
            $imageName = 'speaker_' . Str::slug($request->speaker_name) . '_' . time() . '.webp';
            $targetDirectory = storage_path('app/public/flyers');
            $finalTargetPath = $targetDirectory . '/' . $imageName; // Esta es la ruta donde se guardará la imagen final

            // Crear el directorio de destino si no existe
            if (!File::isDirectory($targetDirectory)) {
                File::makeDirectory($targetDirectory, 0775, true, true); // true para recursivo y para visibilidad pública
                Log::info('Created target directory: ' . $targetDirectory);
            }

            // Esta variable es ahora redundante ya que usamos $tempFilePathForProcessing
            // Pero la mantenemos para el `finally` si el flujo de la excepción la requiere.
            $processedForPythonTempPath = $tempFilePathForProcessing;

            try {
                // Instanciar ImageManager (asegúrate de tener 'use Intervention\Image\ImageManager;' y el Driver adecuado)
                $manager = new ImageManager(new Driver()); // O new Imagick\Driver() si usas ImageMagick

                // PASO 1: Leer la imagen subida, redimensionarla a 400x400 (cover) y guardarla de vuelta en el temporal.
                // Esto asegura que Python trabaje con una imagen de 400x400.
                $manager->read($tempFilePathForProcessing)->cover(400, 400)->toWebp(quality: 100)->save($tempFilePathForProcessing);
                Log::info('Imagen covereada (400x400) guardada en temporal para procesamiento Python: ' . $tempFilePathForProcessing);

                // PASO 2: Ejecutar script Python para detectar el ojo y eliminar el fondo.
                // Python lee $tempFilePathForProcessing, lo procesa y lo sobrescribe.
                $command = config('services.eye_detection.command') . ' ' . escapeshellarg($tempFilePathForProcessing);
                $output = shell_exec($command);
                $data = json_decode($output, true);
                $eyeY = $data['eye_y'] ?? null;

                // PASO 3: Cargar la imagen *PROCESADA POR PYTHON* (ahora sin fondo) desde el mismo archivo temporal.
                $imageAfterPythonProcessing = $manager->read($tempFilePathForProcessing);

                // PASO 4: Procesar la imagen final basándose en la detección del ojo
                if ($eyeY && is_numeric($eyeY)) {
                    $eyeY = intval($eyeY);
                    // Calcular el desplazamiento vertical para que el ojo quede a 170px
                    $offsetY = 170 - $eyeY;

                    // Crear un nuevo lienzo de 400x400 con fondo **transparente** (por defecto sin color)
                    $finalCanvas = $manager->create(400, 400);

                    // Colocar la imagen procesada por Python (con fondo eliminado) en el lienzo final
                    // con el desplazamiento vertical calculado.
                    $finalCanvas->place($imageAfterPythonProcessing, 'top-left', 0, $offsetY);
                    // Guardar la imagen final en la ruta de destino.
                    $finalCanvas->toWebp(quality: 100)->save($finalTargetPath);
                    Log::info("Ojo detectado (Y=$eyeY). Fondo eliminado. Imagen alineada verticalmente a 170px y guardada en: " . $finalTargetPath . " con fondo transparente.");
                } else {
                    // Si no se detectó el ojo, la imagen ya está en 400x400 y tiene el fondo eliminado por Python.
                    // Simplemente se guarda esa imagen procesada en la ruta final sin desplazamiento vertical.
                    $imageAfterPythonProcessing->toWebp(quality: 100)->save($finalTargetPath);
                    Log::info("No se detectó ojo. Fondo eliminado. Imagen covereada y guardada en: " . $finalTargetPath);
                }

                Log::info('Proceso de imagen completado. Resultado final en: ' . $finalTargetPath);

                // Puedes retornar el $imageName para guardarlo en la base de datos o lo que necesites.
                // return $imageName;

            } catch (\Throwable $e) {
                // Captura cualquier error durante el procesamiento o la detección del ojo.
                Log::error('Error durante procesamiento de imagen o detección de ojos: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
                $imageUploadFailedDueToPhpLimits = true; // Asegúrate de que esta variable esté declarada y se use.
            } finally {
                // Finalmente, siempre asegúrate de eliminar el archivo temporal utilizado.
                if (File::exists($tempFilePathForProcessing)) {
                    File::delete($tempFilePathForProcessing);
                    Log::info('Archivo temporal de procesamiento eliminado: ' . $tempFilePathForProcessing);
                }
            }
        }else {
            if ($request->input('speaker_image_sent') === '1') {
                $imageUploadFailedDueToPhpLimits = true;
                Log::warning('speaker_image_sent fue 1 pero hasFile() devolvió false. Puede ser un límite de PHP o fallo de subida.');
            }
        }

        $eventPhone = $request->event_phone;
        $eventPhoneCountry = $request->event_phone_country ?? config('app.iso2', 'CO');

        $phoneValidationFailed = false;
        $formattedPhone = null;

        if ($eventPhone) {
            if (!$this->phoneService->isValid($eventPhone, $eventPhoneCountry)) {
                $phoneValidationFailed = true;
                Log::warning("Número de teléfono inválido para país $eventPhoneCountry: $eventPhone");
            } else {
                $formattedPhone = $this->phoneService->formatNational($eventPhone, $eventPhoneCountry);
                Log::info("Número de teléfono formateado: $formattedPhone");
            }
        }

        $formattedInternational = null;
        if ($eventPhone && !$phoneValidationFailed) {
            $formattedInternational = $this->phoneService->formatInternational($eventPhone, $eventPhoneCountry);
        }

        // Construir los datos que se van a guardar
        $dataToSave = [
            'format' => $request->flyer_format,
            'theme' => session('current_flyer_theme', config('flyer.default_theme')),
            'presenters' => config('flyer.presenters'),
            'mainTitle' => $request->mainTitle,
            'subtitle' => $request->subtitle,
            'event' => [
                'title' => config('flyer.event.title'),
                'date' => $request->event_date,
                'time' => $request->event_time,
                'platform' => $request->event_platform,
                'platform_details' => $request->event_platform_details,
                'phone_country' => $eventPhoneCountry,
                'phone' => $formattedPhone ?? $eventPhone,
                'phone_international' => $formattedInternational ?? null,
            ],
            'speaker' => [
                'name' => $request->speaker_name,
                'title' => $request->speaker_title,
                'quote' => $request->speaker_quote,
                'image' => $imageName,
            ],
            'cta' => [
                'button_text' => config('flyer.cta.button_text'),
                'link' => $request->cta_link,
                'footer_text' => config('flyer.cta.footer_text'),
            ],
        ];
        if (auth()->check()) {
            $dataToSave['email'] = auth()->user()->email;
        }

        // Guardar los datos actualizados en el archivo JSON principal
        $this->saveFlyerData($dataToSave);
        Log::info('Flyer data saved to JSON.');

        // --- LÓGICA CONDICIONAL PARA EL FORMATO Y EL ENLACE COMPARTIDO ---
        // Comprobar si el formato ha cambiado respecto al formato guardado previamente
        $formatChanged = ($request->flyer_format !== ($currentFlyerData['format'] ?? config('flyer.default_format')));
        // Preparar redirección base
        $redirect = redirect()->route('flyer.show');

        // Construir array de mensajes flash
        $flashData = [];
        $successMessage = 'Flyer actualizado exitosamente.';

        if ($formatChanged) {
            $successMessage = 'Formato del flyer actualizado exitosamente.';
            Log::info('Formato del flyer cambiado. No se generará enlace compartido.');
            session()->forget('flyer_was_shared');
        } else {
            $sharedPath = $this->saveSharedFlyerForAnonymous($dataToSave);
            $pathParts = explode('/', $sharedPath);
            $uuid = $pathParts[2] ?? null;
            $filename = $pathParts[3] ?? null;

            $longSharedLink = route('flyer.shared.anon', [
                'uuid' => $uuid,
                'filename' => $filename
            ]);

            $sharedLink = $this->shortener->generate($longSharedLink).'?lang='.config('app.iso2');

            // Si el enlace contiene 'pending', actualizar el JSON compartido con el short code extraído
            if (strpos($dataToSave['cta']['link'], 'pending') !== false) {
                $newZoomLink = str_replace('/u/', '/j/', $sharedLink);

                $pendingRaw = 'https://zoom.us/j/pending';
                $pendingEncoded = urlencode($pendingRaw);

                $updatedLink = $dataToSave['cta']['link'];

                // Caso directo (no codificado)
                if (strpos($updatedLink, $pendingRaw) !== false) {
                    $updatedLink = str_replace($pendingRaw, $newZoomLink, $updatedLink);
                }
                // Caso WhatsApp (codificado)
                else if (strpos($updatedLink, $pendingEncoded) !== false) {
                    $updatedLink = str_replace($pendingEncoded, urlencode($newZoomLink), $updatedLink);
                }

                // Actualizar el JSON compartido
                $sharedFullPath = "flyers/shared/{$uuid}/{$filename}";
                $jsonData = Storage::disk('public')->get($sharedFullPath);
                $sharedData = json_decode($jsonData, true);
                $sharedData['cta']['link'] = $updatedLink;
                Storage::disk('public')->put($sharedFullPath, json_encode($sharedData, JSON_PRETTY_PRINT));
                Log::info("Updated shared flyer with Zoom link: " . $updatedLink);
            }

            Log::info('Shared link generated: ' . $sharedLink);
                
            FlyerJob::dispatch($sharedLink, $uuid, $filename);

            $flashData['shared_link'] = $sharedLink;
        }

        if ($imageUploadFailedDueToPhpLimits) {
            $flashData['warning'] = 'La imagen no pudo subirse. Es probable que exceda el tamaño máximo permitido por el servidor (verifique límites en php.ini) o el tipo de archivo no sea compatible.';
            Log::warning('Redirecting with image upload warning.');
        } else {
            $flashData['success'] = $successMessage;
            Log::info('Redirecting with success message.');
        }

        Log::info('--- FIN DE PROCESO DE SUBIDA DE FLYER ---');
        
        if ($phoneValidationFailed) {
            $flashData['warning'] = 'El número de teléfono no es un móvil válido para el país seleccionado.';
        }

        // ✅ Aplicar los mensajes con ->with() en el return
        return $redirect->with($flashData);
    }

    /**
     * Resetea el tema y el formato de la sesión a sus valores por defecto.
     * Nota: Esto NO borra el archivo JSON guardado.
     */
    public function reset()
    {
        session()->forget('current_flyer_theme');
        session()->forget('current_flyer_format');
        session()->forget('flyer_format');
        // Opcional: Si quieres borrar el JSON para un reset completo (usar con precaución en producción)
        // Storage::disk('public')->delete('flyers/flyer_1.json');
        return redirect()->route('flyer.show')->with('message', 'Configuración de vista reseteada.');
    }
    
    public function restoreDefault()
    {
        $flyerId = $this->getAdminSessionId();
        $filePath = "flyers/flyer_{$flyerId}.json";

        if (Storage::disk('public')->exists($filePath)) Storage::disk('public')->delete($filePath);

        session()->forget('current_flyer_format');
        session()->forget('current_flyer_theme');

        return redirect()->route('flyer.show')->with('message', 'Flyer restaurado a formato y tema por defecto.');
    }

    /**
     * MÉTODOS AUXILIARES para cargar y guardar los datos del flyer.
     * Asumen un ID fijo para el flyer de administración (ej. 1).
     */
    protected function loadFlyerData($id = null)
    {
        $id = $id ?? $this->getAdminSessionId(); // Usa sesión si no se pasa explícito
        $filePath = "flyers/flyer_{$id}.json";

        if (Storage::disk('public')->exists($filePath)) {
            return json_decode(Storage::disk('public')->get($filePath), true);
        }

        $default = config('flyer');
        $default['format'] = $default['default_format'];
        return $default;
    }

    protected function saveFlyerData(array $data, $id = null)
    {
        $id = $id ?? $this->getAdminSessionId();
        $filePath = "flyers/flyer_{$id}.json";
        Storage::disk('public')->put($filePath, json_encode($data, JSON_PRETTY_PRINT));
    }

    protected function getAdminSessionId(): string
    {
        if (!session()->has('admin_flyer_id')) {
            session(['admin_flyer_id' => (string) Str::uuid()]);
        }
        return session('admin_flyer_id');
    }
    
    public function newFlyerSession()
    {
        session()->forget('admin_flyer_id');
        session()->forget('flyer_was_shared');
        return redirect()->route('flyer.show')->with('message', 'Nuevo flyer iniciado.');
    }
    
    protected function getAnonymousVisitorId(): string
    {
        if (!session()->has('flyer_anon_id')) {
            session(['flyer_anon_id' => (string) Str::uuid()]);
        }
        return session('flyer_anon_id');
    }

    protected function saveSharedFlyerForAnonymous(array $data): string
    {
        $anonId = $this->getAnonymousVisitorId();
        $sharedDir = storage_path("app/public/flyers/shared/{$anonId}");

        if (!File::exists($sharedDir)) {
            File::makeDirectory($sharedDir, 0775, true);
            chgrp($sharedDir, 'www-data'); // ✅ Asegura grupo
            chmod($sharedDir, 0775);       // ✅ Asegura permisos rwxrwxr-x
        }

        $this->cleanOldFlyers($sharedDir);

        $timestamp = now()->timestamp;
        $random = Str::random(6);
        $fileName = "flyer_{$timestamp}_{$random}.json";
        $filePath = "{$sharedDir}/{$fileName}";

        File::put($filePath, json_encode($data, JSON_PRETTY_PRINT));
        chmod($filePath, 0664);      // ✅ Permisos rw-rw-r--
        chgrp($filePath, 'www-data'); // ✅ Grupo

        return "flyers/shared/{$anonId}/{$fileName}";
    }

    protected function cleanOldFlyers(string $sharedDir): void
    {
        $expirationDays = config('flyer.flyer_expiration_days');
        $maxFlyers = config('flyer.max_flyers_per_user');

        $files = collect(File::files($sharedDir))->sortBy(fn($f) => $f->getCTime());

        // Por tiempo
        $now = now();
        foreach ($files as $file) {
            if ($now->diffInDays(Carbon::createFromTimestamp($file->getCTime())) >= $expirationDays) {
                File::delete($file->getRealPath());
            }
        }

        // Por cantidad
        $remaining = collect(File::files($sharedDir))->sortBy(fn($f) => $f->getCTime());
        if ($remaining->count() > $maxFlyers) {
            $toDelete = $remaining->take($remaining->count() - $maxFlyers);
            foreach ($toDelete as $file) {
                File::delete($file->getRealPath());
            }
        }
    }
    
    public function showSharedAnonymous($uuid, $filename)
    {
        $filePath = "flyers/shared/{$uuid}/{$filename}";

        if (!Storage::disk('public')->exists($filePath)) {
            abort(404, 'Flyer compartido no encontrado.');
        }

        $jsonData = Storage::disk('public')->get($filePath);
        $data = json_decode($jsonData, true);

        $activeThemeName = $data['theme'] ?? config('flyer.default_theme');
        $theme = array_merge(
            config('flyer.themes.default'),
            config("flyer.themes.{$activeThemeName}", [])
        );

        $activeFormatName = $data['format'] ?? config('flyer.default_format');
        $activeFormatView = config("flyer.formats.{$activeFormatName}.view");
        
        return view($activeFormatView, [
            'theme' => $theme,
            'data' => $data,
            'is_shared_view' => true,
            'current_format_name' => $activeFormatName,
            'uuid' => $uuid,
            'filename' => $filename,
        ]);
    }

    public function confirmShared(Request $request)
    {
        session()->put('flyer_was_shared', true);
        return response()->json(['message' => 'OK']);
    }

}
