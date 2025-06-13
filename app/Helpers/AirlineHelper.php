<?php

namespace App\Helpers;

class AirlineHelper
{
    public static function allAirlineDomains(): array
    {
        return collect(config('aerolineas'))
            ->filter(fn($a) => is_array($a) && isset($a['domains']))
            ->pluck('domains')
            ->flatten()
            ->unique()
            ->values()
            ->toArray();
    }

    public static function allAirlineKeywords(): array
    {
        return collect(config('aerolineas'))
            ->filter(fn($a) => is_array($a) && isset($a['keywords']))
            ->pluck('keywords')
            ->flatten()
            ->unique()
            ->values()
            ->toArray();
    }

    public static function allDefaultKeywords(): array
    {
        return [
            'reserva', 'reservation', 'booking', 'confirmación', 'itinerario',
            'billete', 'ticket', 'check-in', 'recibo', 'compra'
        ];
    }
}
