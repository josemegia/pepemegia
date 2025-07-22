<?php

namespace App\Support;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Collection;
use App\Models\LangMapping;

class LocaleManager
{
    /**
     * Obtiene los locales disponibles y sus banderas asociadas
     *
     * @return Collection [locale => flagCode]
     */
    public static function getAvailableLocales(): Collection
    {
        $manualOverrides = Cache::rememberForever('lang_country_mappings', function () { return LangMapping::pluck('country_code', 'lang_code')->all(); })??[];
        $requiredFile = Config::get('menu.file_check', '');
        $langPath = lang_path();
        $locales = collect();

        if (!File::isDirectory($langPath)) {
            return $locales;
        }

        collect(File::directories($langPath))
            ->map(fn ($dir) => basename($dir))
            ->filter(fn ($locale) =>
                preg_match('/^[a-z]{2}([_-][A-Za-z]{2,})*$/', $locale) &&
                File::exists("$langPath/$locale/$requiredFile")
            )
            ->each(function ($locale) use (&$locales, $manualOverrides) {
                $flag = $manualOverrides[$locale]
                    ?? strtolower(last(preg_split('/[-_]/', $locale)));
                $locales->put($locale, $flag);
            });

        return $locales;
    }
        public static function isSupportedLocale(string $locale): bool
    {
        return self::getAvailableLocales()->has($locale);
    }

}

