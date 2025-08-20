<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

use App\Services\ShortUrlService;

// Controllers
use App\Http\Controllers\FlyerController;
use App\Http\Controllers\ShortUrlController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\PresentacionController;
use App\Http\Controllers\VentasController;
use App\Http\Controllers\IframeController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\Admin\AdminUsersController;
use App\Http\Controllers\Admin\RecaptchaBlockController;
use App\Http\Controllers\Admin\AirportController;
use App\Http\Controllers\Admin\StayController;

// Página principal y privacidad
Route::view('/', 'inicio')->name('inicio');
Route::view('/privacidad', 'privacidad')->name('privacidad');

// URL corta
Route::get('/u/{code}', [ShortUrlController::class, 'show'])->name('shorturl.show');
Route::get('/j/{code}', [ShortUrlController::class, 'zoom'])->name('shorturl.zoom');

// Socialite auth
Route::prefix('auth')->name('socialite.')->group(function () {
    Route::get('/{provider}/redirect', [SocialiteController::class, 'redirectToProvider'])->name('redirect');
    Route::get('/{provider}/callback', [SocialiteController::class, 'handleProviderCallback'])->name('callback');
});

// Plan/presentación
Route::prefix('plan')->name('plan.')->group(function () {
    Route::get('/', function (Request $request) {
        return redirect()->route('plan.show', [
            'dia' => 1,
            'divisa' => strtolower(config('app.iso2'))
        ]);
    })->name('index');
    Route::get('/presentacion/{dia}', [PresentacionController::class, 'show'])->name('show');

});

// Ventas
Route::prefix('ventas')->name('ventas.')->group(function () {
    Route::get('/', [VentasController::class, 'presentacion'])->name('index');

    Route::get('/form', [VentasController::class, 'form'])->name('form');
    Route::post('/form', [VentasController::class, 'storeOrUpdate'])->name('storeOrUpdate');

});


// Flyers
Route::prefix('flyer')->name('flyer.')->group(function () {
    Route::get('/', [FlyerController::class, 'show'])->name('show');
    Route::get('/editar', [FlyerController::class, 'showForm'])->name('edit');
    Route::post('/actualizar', [FlyerController::class, 'update'])->name('update');
    Route::get('/reset', [FlyerController::class, 'reset'])->name('reset');
    Route::get('/restore-default', [FlyerController::class, 'restoreDefault'])->name('restore');
    Route::get('/view/{flyerId}', [FlyerController::class, 'showShared'])->name('shared');
    Route::get('/view/{uuid}/{filename}', [FlyerController::class, 'showSharedAnonymous'])->name('shared.anon');
    Route::post('/confirm-shared', [FlyerController::class, 'confirmShared'])->name('confirmShared');
    Route::get('/new', [FlyerController::class, 'newFlyerSession'])->name('new');
    Route::get('/next-format', function () {
        $formats = array_keys(config('flyer.formats'));
        $current = request('current', config('flyer.default_format'));
        $next = $formats[(array_search($current, $formats) + 1) % count($formats)];
        return redirect()->route('flyer.show', ['format' => $next]);
    })->name('nextFormat');
});

// Rutas autenticadas
Route::middleware('auth')->group(function () {
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/logout', function (Request $request) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect('/login');})->name('logout');
        Route::view('/dashboard', 'auth.dashboard')->name('dashboard');
        Route::get('/edit', [ProfileController::class, 'edit'])->name('edit');
        Route::put('/', [ProfileController::class, 'update'])->name('update');
        Route::put('/password', [ProfileController::class, 'updatePassword'])->name('update-password');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });
    // Admin
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('iframe', [IframeController::class, 'show'])->name('iframe');
        Route::resource('users', AdminUsersController::class);
        Route::view('/dashboard', 'admin.dashboard')->name('dashboard');
        Route::prefix('recaptcha')->name('recaptcha.')->group(function () {
            Route::get('/', [RecaptchaBlockController::class, 'index'])->name('index');
            Route::delete('/{ip}', [RecaptchaBlockController::class, 'destroy'])->name('destroy');
        });

        // Rutas de Configuración (placeholders)
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::view('/general', 'admin.settings.general')->name('general');
            Route::view('/email', 'admin.settings.email')->name('email');
            Route::view('/integrations', 'admin.settings.integrations')->name('integrations');
        });

        // Aeropuertos
        Route::prefix('airports')->name('airports.')->group(function () {
            Route::get('/tool', [AirportController::class, 'showAirportAdminToolPage'])->name('tool');
            Route::get('/viajes', [AirportController::class, 'viajes'])->name('viajes.dashboard');
        });

        // Estancias por País
        Route::prefix('stays')->name('stays.')->group(function () {
            Route::get('/', [AirportController::class, 'viajes'])->name('index');
        });
    });
});

Route::fallback(
    function (Request $request, ShortUrlService $shortUrlService) {
        $path = ltrim($request->path(), '/');
        return $shortUrlService->handleFallback($path);
    }
);
