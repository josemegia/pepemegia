<?php

namespace App\Helpers;

class CurrencyHelper
{
    public static function getFlagData($iso2)
    {
        return [
            'name'  => \Locale::getDisplayRegion('und_' . strtoupper($iso2), app()->getLocale()),
            'emoji' => implode('', array_map(fn ($c) => mb_chr(0x1F1E6 - ord('A') + ord($c)), str_split(strtoupper($iso2)))),
            'flag'  => asset('flags/' . strtolower($iso2) . '.svg'),
        ];
    }

    public static function divisa($lp, $cambio, $divisa): string
    {
        $importe = round($lp * $cambio);
        return self::divisaBruta($importe, $divisa);
    }

    public static function divisaBruta(float $importe, string $divisa): string
    {
        $iso2 = strtolower(substr($divisa, -2));
        $config = config('menu.divisas')[$iso2] ?? [];

        // Usa 'idioma' del config o español como fallback
        $formatter = new \NumberFormatter($config['idioma'] ?? 'es', \NumberFormatter::CURRENCY);

        // Usa decimales según 'dec'
        $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, ($config['dec'] ?? true) ? 2 : 0);

        return $formatter->formatCurrency($importe, $config['code'] ?? $formatter->getTextAttribute(\NumberFormatter::CURRENCY_CODE));
    }
}
