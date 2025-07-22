<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Support\LocaleManager;
use App\Models\IsoCountryCode;
use App\Models\LangMapping;
use Illuminate\Support\Facades\Cache;

class LocaleMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // 1. Idiomas realmente disponibles en /resources/lang
        $availableLocales = LocaleManager::getAvailableLocales()->keys()->toArray();

        // 2. Mapeo country_code <-> lang_code (opcional, para variantes)
        $menuLang = Cache::rememberForever('lang_country_mappings', function () {
            return LangMapping::pluck('country_code', 'lang_code')->all();
        }) ?? [];

        // 3. --- GESTIÓN PRIORITARIA: ?lang=XX como idioma O país ---
        $locale = null;
        if ($request->has('lang')) {
            $requested = str_replace('-', '_', strtolower($request->get('lang')));
            if (strpos($requested, '_') !== false) {
                [$lang, $region] = explode('_', $requested, 2);
                $requested = $lang . '_' . strtoupper($region);
            }

            // Si ?lang=XX es un idioma ya existente
            if (in_array($requested, $availableLocales)) {
                $locale = $requested;
            } else {
                // Si no existe, intentamos ver si es código de país (ISO2)
                $langFromIso2 = IsoCountryCode::getLangForIso2(strtoupper($requested));
                if ($langFromIso2 && in_array($langFromIso2, $availableLocales)) {
                    $locale = $langFromIso2;
                } elseif ($langFromIso2) {
                    \Log::info("Lanzando PrepareLocaleWithGPTGemini para: $langFromIso2");
                    dispatch(new \App\Jobs\PrepareLocaleWithGPTGemini($langFromIso2, strtoupper($requested)));
                    $locale = config('app.locale', 'es');
                } else {
                    // No corresponde a idioma ni país conocido: fallback
                    $locale = config('app.locale', 'es');
                }
            }
            
            session(['locale' => $locale]);
            app()->setLocale($locale);
            return $next($request); // ¡Salimos aquí si hay ?lang!
        }

        // 4. Si no hay ?lang, miramos la sesión
        $locale = session('locale');

        // 5. No existe en sesión o es inválido
        if (!$locale || !in_array($locale, $availableLocales)) {
            $locale = null;
            config(['app.iso2' => $request->server('HTTP_X_COUNTRY_CODE', 'es')]);
            
            $countryCode = strtoupper(config('app.iso2'));

            // --- Detección por variantes de país (localeKey tipo pt_BR, zh_CN, etc) ---
            foreach (array_keys($menuLang) as $localeKey) {
                if (
                    preg_match('/_(\w{2})$/', $localeKey, $matches)
                    && strtoupper($matches[1]) === $countryCode
                    && in_array($localeKey, $availableLocales)
                ) {
                    $locale = $localeKey;
                    break;
                }
            }

            // --- Detección por JSON de país (especial Canadá y otros) ---
            if (!$locale && $countryCode) {
                $jsonPath = resource_path('countries/data/' . strtolower($countryCode) . '.json');
                if (file_exists($jsonPath)) {
                    $data = json_decode(file_get_contents($jsonPath), true);

                    // Lógica Canadá especial
                    if ($countryCode === 'CA' && isset($data['languages'])) {
                        $provinceCode = strtoupper($request->server('HTTP_X_REGION', ''));
                        if ($provinceCode === 'QC' && in_array('fr', $availableLocales)) {
                            $locale = 'fr';
                        } elseif (
                            $provinceCode === 'NB'
                            && in_array('fr', $availableLocales)
                            && in_array('en', $availableLocales)
                        ) {
                            $userPreferred = substr($request->server('HTTP_ACCEPT_LANGUAGE', ''), 0, 2);
                            if (in_array($userPreferred, ['en', 'fr'])) {
                                $locale = $userPreferred;
                            } else {
                                $locale = 'en';
                            }
                        } elseif (in_array('en', $availableLocales)) {
                            $locale = 'en';
                        }
                    }

                    // Busca idioma disponible por ISO3 del JSON
                    if (!$locale && isset($data['languages']) && is_array($data['languages'])) {
                        foreach (array_keys($data['languages']) as $iso3) {
                            if ($iso3 === 'por') {
                                // Portugués especial
                                if ($countryCode === 'BR' && in_array('pt_BR', $availableLocales)) {
                                    $locale = 'pt_BR';
                                } elseif ($countryCode === 'PT' && in_array('pt', $availableLocales)) {
                                    $locale = 'pt';
                                } else {
                                    $locale = in_array('pt_BR', $availableLocales)
                                        ? 'pt_BR'
                                        : (in_array('pt', $availableLocales) ? 'pt' : null);
                                }
                            } else {
                                $localeCandidate = IsoCountryCode::iso3toIso2($iso3);
                                if ($localeCandidate && in_array($localeCandidate, $availableLocales)) {
                                    $locale = $localeCandidate;
                                }
                            }
                            if ($locale) break;
                        }
                    }
                }
            }

            // 6. Fallback: busca por tabla IsoCountryCode si aún no hay locale
            if (!$locale && $countryCode) {
                $lang = IsoCountryCode::getLangForIso2($countryCode);
                if ($lang && in_array($lang, $availableLocales)) {
                    $locale = $lang;
                } elseif ($lang) {
                    dispatch(new \App\Jobs\PrepareLocaleWithGPTGemini($lang,$countryCode));
                    $locale = config('app.locale', 'es');
                }
            }

            // 7. Último recurso: por defecto
            if (!$locale) {
                $locale = config('app.locale', 'es');
            }

            session(['locale' => $locale]);
        }

        // --- Aplica el locale a toda la app para esta request ---
        app()->setLocale($locale);

        // --- CONTADOR DE PAÍSES (lista única en sesión) ---
        $userAgent = strtolower($request->header('User-Agent', ''));
        $isBot = (
            strpos($userAgent, 'bot') !== false ||
            strpos($userAgent, 'crawl') !== false ||
            strpos($userAgent, 'spider') !== false
        );

        if (!$isBot) {
            $iso2 = $request->has('c')
                ? strtoupper($request->input('c'))
                : (!empty($countryCode) ? $countryCode : null);

            // Recupera el array de países ya contados esta sesión (garantiza array)
            $alreadyCounted = session('counter_countries', []);
            if (!is_array($alreadyCounted)) {
                $alreadyCounted = [];
            }

            if ($iso2 && !in_array($iso2, $alreadyCounted)) {
                IsoCountryCode::where('iso2', $iso2)->increment('counter');
                $alreadyCounted[] = $iso2;
                session(['counter_countries' => $alreadyCounted]);
            }
        }

        return $next($request);
    }
}
