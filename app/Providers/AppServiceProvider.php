<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;

use Illuminate\Support\{
    Collection,
    ServiceProvider,
    Str,
    Facades\Blade,
    Facades\File,
    Facades\RateLimiter,
    Facades\View,
    Facades\Gate
    };

use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Fortify\Fortify;

use App\Actions\Fortify\CreateNewUser;
use App\Http\Middleware\CheckAdminRole;
use App\View\Components\LanguageSelector;
use App\Support\LocaleManager;

use App\Models\IsoCountryCode; 

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerUserBindings();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(Router $router): void
    {
        $this->configureAppDomain();
        umask(0002);
        $this->registerBladeComponents();
        $this->registerGates();
        $this->registerMiddlewareAliases($router);
        $this->configureRateLimiting();
        $this->registerFortifyViews();
        $this->shareAvailableLocales();
    }
    
    protected function configureAppDomain(): void
    {
        if (!app()->runningInConsole() && request()->getHost()) {
            $host = request()->getHost();
            $schemeHost = request()->getSchemeAndHttpHost();

            $domainMap = config('app.domain_map', []);
            $name = $domainMap[$host] ?? $domainMap['default'] ?? 'PepeMegia.com';

            config([
                'app.url'                           => $schemeHost,
                'app.iso2'                          => request()->server('HTTP_X_COUNTRY_CODE', 'es'),
                'app.4life'                         => request()->server('HTTP_X_PREFIX', 'usspanish.4life.com'),
                'app.region'                        => request()->server('HTTP_X_REGION',''),
                'app.country'                       => request()->server('HTTP_X_COUNTRY_NAME',''),
                'app.city'                          => request()->server('HTTP_X_CITY',''),
                'filesystems.public.url'            => $schemeHost.'/storage',
                'fourlife.default.email'            => 'admin@'.$host,
                'fourlife.default.dominio'          => $host,
                'services.twitter-oauth-2.redirect'         => $schemeHost . env('TWITTER_REDIRECT_URI'),
                'services.github.redirect'          => $schemeHost . env('GITHUB_REDIRECT_URI'),
                'services.google.redirect'          => $schemeHost . env('GOOGLE_OAUTH_REDIRECT_URI'),
            ]);

            View::share('appName', $name);
        }
    }

    protected function registerGates(): void
    {
        Gate::before(function ($user, $ability) {
            // Usamos 'hasMethod' para evitar errores si el objeto de usuario no es el esperado.
            if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
                return true;
            }
        });
    }

    protected function registerUserBindings(): void
    {
        $this->app->singleton(CreatesNewUsers::class, CreateNewUser::class);
    }

    protected function registerBladeComponents(): void
    {
        Blade::component('language-selector', LanguageSelector::class);
    }

    protected function registerMiddlewareAliases(Router $router): void
    {
        $router->aliasMiddleware('admin', CheckAdminRole::class);
    }

    protected function configureRateLimiting(): void
    {
        $this->app->booted(function () {
            RateLimiter::for('login', function (Request $request) {
                $key = Str::transliterate(
                    Str::lower($request->input(Fortify::username())) . '|' . $request->ip()
                );
                return Limit::perMinute(5)->by($key);
            });

            RateLimiter::for('two-factor', function (Request $request) {
                return Limit::perMinute(5)->by($request->session()->get('login.id'));
            });
        });
    }

    protected function registerFortifyViews(): void
    {
        Fortify::loginView(fn () => view('auth.login'));
        Fortify::registerView(fn () => view('auth.register'));
        Fortify::requestPasswordResetLinkView(fn () => view('auth.forgot-password'));
        Fortify::resetPasswordView(fn (Request $request) => view('auth.reset-password', ['request' => $request]));
    }

    protected function shareAvailableLocales(): void
    {
        $locales = LocaleManager::getAvailableLocales();
        if (is_a($locales, Collection::class)) {
            $locales = $locales->toArray();
        } elseif (!is_array($locales)) {
            $locales = ['es' => 'es'];
        }

        $iso2s = array_unique(array_values($locales));

        $iso2Counters = IsoCountryCode::whereIn('iso2', array_map('strtolower', $iso2s))
            ->pluck('counter', 'iso2')
            ->mapWithKeys(fn($counter, $iso2) => [strtolower($iso2) => $counter])
            ->toArray();

        uksort($locales, function ($localeA, $localeB) use ($locales, $iso2Counters) {
            $isoA = strtolower($locales[$localeA]);
            $isoB = strtolower($locales[$localeB]);
            $countA = $iso2Counters[$isoA] ?? 0;
            $countB = $iso2Counters[$isoB] ?? 0;

            if ($countA === $countB) {
                return strcmp($isoA, $isoB);
            }
            return $countB <=> $countA;
        });

        View::share('availableLocales', $locales);
    }
}
