<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\ReservasController;

Route::prefix('google')->group(function () {
    Route::get('/auth', [GoogleAuthController::class, 'redirectToGoogle'])->name('google.auth');
    Route::get('/callback', [GoogleAuthController::class, 'handleGoogleCallback'])->name('google.callback');
});

Route::get('/', [ReservasController::class, 'dashboard']);

// Ruta para la herramienta de administración de aeropuertos, ahora usando ReservasController
// Puedes decidir si va dentro del grupo 'auth' o no, según tus necesidades de seguridad.
Route::get('/aeropuertos', [ReservasController::class, 'showAirportAdminToolPage'])->name('admin.aeropuertos');

// O si no necesitas autenticación para esta página específica (solo para pruebas):
// Route::get('/admin/aeropuertos-tool', [ReservasController::class, 'showAirportAdminToolPage'])->name('admin.aeropuertos.tool');