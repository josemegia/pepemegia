<?php
// routes/api.php

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Admin\StayController;
use App\Http\Controllers\Admin\AirportController;
use App\Models\Reserva;
use App\Models\Pasajero;

//Route::get('/user', function (Request $request) {return $request->user();})->middleware('auth:sanctum')->name('api.user');

Route::get('/verificar-afiliado/{codigo}', function (Request $request, $codigo) {
    // Tomamos el dominio desde la cabecera o desde .env/config
    $prefix = config('app.4life') ?? config('app.domain_prefix', 'usspanish.4life.com');
    // Parche provisional: devolver respuesta exitosa sin consultar Playwright
    return response()->json([
        'exists' => true,
        'url' => "https://{$prefix}/{$codigo}",
        'finalUrl' => "https://{$prefix}/{$codigo}",
        'status' => 200,
        'tituloPagina' => 'Página de afiliado',
        'textoImportante' => 'Afiliado válido',
    ], 200);
    /*
    try {
        // Hacemos la consulta al servidor de Playwright local
        $response = Http::post('http://localhost:7378/verificar', [
            'codigo' => $codigo,
            'prefix' => $prefix // Puedes usar esto para múltiples dominios si lo deseas
        ]);

        return response()->json([
            'exists' => $response->json('exists'),
            'url' => $response->json('url'),
            'finalUrl' => $response->json('finalUrl'),
            'status' => $response->json('status'),
            'tituloPagina' => $response->json('tituloPagina'),
            'textoImportante' => $response->json('textoImportante'),
        ], $response->ok() && $response->json('exists') ? 200 : 404);

    } catch (\Exception $e) {
        \Log::error("Error llamando a Playwright para {$codigo}: {$e->getMessage()}");

        return response()->json([
            'exists' => false,
            'error' => 'Error al consultar verificación',
        ], 500);
    }*/
});


Route::prefix('admin')->name('api.admin.')->middleware('auth:sanctum')->group(function () {

    Route::get('/timeline', [AirportController::class, 'timeline'])->name('timeline');
    Route::get('/exportar', [AirportController::class, 'exportar'])->name('exportar');

    Route::prefix('airports')->name('airports.')->group(function () {
        Route::get('/', [AirportController::class, 'index'])->name('index');
        Route::get('/getcountry', [AirportController::class, 'testGetCountry'])->name('getcountry');
        Route::post('/update-references', [AirportController::class, 'updateAirportReferenceData'])->name('update-references');
    });

    Route::prefix('stays')->group(function () {
        Route::get('/', [StayController::class, 'index'])->name('index');
        Route::get('/cronograma', [StayController::class, 'cronograma'])->name('cronograma');
        Route::get('/pasajeros', [StayController::class, 'pasajeros'])->name('pasajeros');
        Route::get('/{id}', [AirportController::class, 'show'])->name('show');  
    });

});
