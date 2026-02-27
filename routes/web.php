<?php

use Illuminate\Http\Request;

use Illuminate\Support\Facades\{
    Storage,
    Session,
    Auth,
    Route
    };

use App\Http\Controllers\{
    FlyerController,
    ShortUrlController,
    GoogleAuthController,
    PresentacionController,
    VentasController,
    IframeController,
    PosterController,
    ReelDownloaderController,
    FlAdvisorController,

    Auth\TwoFactorAuthenticationController,
    Auth\TwoFactorRecoveryCodesController,
    Auth\TwoFactorQrCodeController,
    Auth\SocialiteController,
    Auth\ProfileController,

    Admin\FlAdminProductsController,
    Admin\RecaptchaBlockController,
    Admin\AdminUsersController,
    Admin\AirportController,
    Admin\StayController,
    Admin\FlDocumentsController,
    Admin\FlApiClientsController

    };

use App\Services\ShortUrlService;

Route::get('/presentacion', function () { return view('presentacion'); })->name('presentacion');

// Seeders
Route::middleware(['auth'])->group(function () {
    Route::view('/admin/docs/seeders', 'admin.docs.seeders');
});

// 4Life IA Advisor
Route::prefix('4life')->name('fourlife.')->group(function () {
    Route::get("/", [FlAdvisorController::class, "index"])->name("chat");
    Route::post("/consult", [FlAdvisorController::class, "consult"])->name("consult");
    Route::post("/save-code", [FlAdvisorController::class, "saveCode"])->name("save-code");
});

// ─── Descargador de Reels ────────────────────────────
Route::middleware(['auth'])->prefix('reels')->name('reels.')->group(function () {
    Route::get('/', [ReelDownloaderController::class, 'index'])->name('index');
    Route::post('/cookies', [ReelDownloaderController::class, 'uploadCookies'])->name('cookies');
    Route::post('/procesar', [ReelDownloaderController::class, 'procesar'])->name('procesar');
    Route::post('/descargar', [ReelDownloaderController::class, 'descargar'])->name('descargar');
    Route::get('/descargar-archivo/{archivo}', [ReelDownloaderController::class, 'descargarArchivo'])->name('descargar.archivo');
    Route::get('/archivo/{archivo}', [ReelDownloaderController::class, 'verArchivo'])->name('ver');
    Route::delete('/archivos/masivo', [ReelDownloaderController::class, 'eliminarMasivo'])->name('eliminar.masivo');
    Route::delete('/archivo/{archivo}', [ReelDownloaderController::class, 'eliminarArchivo'])->name('eliminar');
});

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

// Rutas autenticadas
Route::middleware('auth')->group(function () {
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/logout', function (Request $request) {Auth::logout();$request->session()->invalidate();$request->session()->regenerateToken();return redirect('/login');})->name('logout');
        Route::view('/dashboard', 'auth.dashboard')->name('dashboard');
        Route::get('/edit', [ProfileController::class, 'edit'])->name('edit');
        Route::put('/', [ProfileController::class, 'update'])->name('update');
        Route::put('/password', [ProfileController::class, 'updatePassword'])->name('update-password');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
        Route::post('/two-factor-authentication', [TwoFactorAuthenticationController::class, 'store'])->name('two-factor.enable');
        Route::delete('/two-factor-authentication', [TwoFactorAuthenticationController::class, 'destroy'])->name('two-factor.disable');
        Route::get('/two-factor-qr-code', [TwoFactorQrCodeController::class, 'show'])->name('two-factor.qr-code');
        Route::get('/two-factor-recovery-codes', [TwoFactorRecoveryCodesController::class, 'index'])->name('two-factor.recovery-codes');
        Route::post('/two-factor-authentication/confirm', [TwoFactorAuthenticationController::class, 'confirm'])->name('two-factor.confirm');
    });

    // Admin
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::prefix('fl-products')->name('fl-products.')->group(function () {
            Route::get('/', [FlAdminProductsController::class, 'index'])->name('index');
            Route::post('/import', [FlAdminProductsController::class, 'import'])->name('import');
            Route::delete('/{country}', [FlAdminProductsController::class, 'destroy'])->name('destroy');
        });
        Route::prefix("documents")->name("documents.")->group(function () {
            Route::get("/", [FlDocumentsController::class, "index"])->name("index");
            Route::post("/", [FlDocumentsController::class, "store"])->name("store");
            Route::get("/{document}/download", [FlDocumentsController::class, "download"])->name("download");
            Route::patch("/{document}/toggle", [FlDocumentsController::class, "toggleActive"])->name("toggle");
            Route::delete("/{document}", [FlDocumentsController::class, "destroy"])->name("destroy");
        });
        Route::prefix("api-clients")->name("api-clients.")->group(function () {
            Route::get("/", [FlApiClientsController::class, "index"])->name("index");
            Route::post("/", [FlApiClientsController::class, "store"])->name("store");
            Route::patch("/{client}/toggle", [FlApiClientsController::class, "toggleActive"])->name("toggle");
            Route::post("/{client}/reset", [FlApiClientsController::class, "resetCounters"])->name("reset");
            Route::get("/{client}/logs", [FlApiClientsController::class, "logs"])->name("logs");
            Route::delete("/{client}", [FlApiClientsController::class, "destroy"])->name("destroy");
        });
        Route::get('iframe', [IframeController::class, 'show'])->name('iframe');
        Route::resource('users', AdminUsersController::class);
        Route::view('/dashboard', 'admin.dashboard')->name('dashboard');

        Route::prefix('recaptcha')->name('recaptcha.')->group(function () {
            Route::get('/', [RecaptchaBlockController::class, 'index'])->name('index');
            Route::delete('/{ip}', [RecaptchaBlockController::class, 'destroy'])->name('destroy');
        });

        // OpenVpn Configs
        Route::prefix('ovpn')->name('ovpn.')->group(function () {
            Route::get('/', function () {
                return view('ovpn');
            })->name('index');

            Route::get('/download/{server}.ovpn', function ($server) {
                $server = basename($server);
                $filename = 'ovpn/' . $server . '.ovpn';
                if (Storage::disk('local')->exists($filename)) {
                    $file = Storage::disk('local')->get($filename);
                    return response($file, 200)
                        ->header('Content-Type', 'application/octet-stream')
                        ->header('Content-Disposition', 'attachment; filename="' . $server . '.ovpn"');
                } else {
                    abort(404, 'File not found.');
                }
            })->name('download');
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

// Poster
Route::prefix('poster')->name('poster.')->group(function () {
    Route::get('/', [PosterController::class, 'index'])->name('index');
    Route::get('/state',  [PosterController::class, 'state'])->name('state.get');
    Route::post('/state', [PosterController::class, 'stateSave'])->name('state.save');
    Route::get('/rebuild-assets', [PosterController::class, 'rebuildAssets']);
    Route::middleware(['auth'])->group(function () {
        Route::post('/rebuild-assets', [PosterController::class, 'rebuildAssets'])
            ->name('assets.rebuild')
            ->middleware('admin');
    });
});

// Si no encuentra la ruta ...
Route::fallback(function (Request $request, ShortUrlService $shortUrlService) {return $shortUrlService->handleFallback(ltrim($request->path(), '/'));});
