<?php
// routes/api.php

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReservasController;
use App\Http\Controllers\TestPdfParserController;
use App\Http\Controllers\EstanciasController;
use App\Models\Reserva;
use App\Models\Pasajero;

Route::get('/test', [TestPdfParserController::class, 'testIberiaPdfParse']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Rutas para reservas
Route::prefix('reservas')->group(function () {

    Route::get('/estadisticas', [ReservasController::class, 'estadisticas']);
    Route::get('/timeline', [ReservasController::class, 'timeline']);
    Route::get('/exportar', [ReservasController::class, 'exportar']);
    Route::get('/{id}', [ReservasController::class, 'show']);

    Route::prefix('aeropuertos')->group(function () {
        Route::get('/', [ReservasController::class, 'index']);
        Route::get('/get-country', [ReservasController::class, 'testGetCountry']);
        Route::post('/update-references', [ReservasController::class, 'updateAirportReferenceData']);
    });

    Route::prefix('estancias')->group(function () {
        Route::get('/', [EstanciasController::class, 'index']);    
        Route::get('/pasajeros', [EstanciasController::class, 'pasajeros']);
        Route::get('/cronograma', [EstanciasController::class, 'cronograma']);
    });

});
