<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    protected $fillable = [
        'pais_iso2', 'idioma',
        'precio_afiliado', 'precio_tienda', 'pvp',
        'precio2_paquete_mes4', 'precio1_paquete_mes4',
        'propuesta_mensual', 'precio_paquete',
        // Calculados
        'ganancia_mes1', 'ganancia1_mes4', 'ganancia2_mes4',
        'precio_mes1', 'precio1_mes4_calc',
        'ganancia_paquete_mes1', 'ganancia1_paquete_mes4', 'ganancia2_paquete_mes4',
        'ganancia_total_mes1', 'ganancia_paquete_mes2', 'ganancia_total_mes2', 'ganancia_total_mes4'
    ];

    public static function boot()
    {
        parent::boot();

        static::saving(function ($venta) {
            // Variables base
            $afiliado = $venta->precio_afiliado;
            $tienda = $venta->precio_tienda;
            $pvp = $venta->pvp;
            $p2_mes4 = $venta->precio2_paquete_mes4;
            $p1_mes4 = $venta->precio1_paquete_mes4;
            $paquete = $venta->precio_paquete;

            // Cálculos
            $venta->ganancia_mes1 = $afiliado - ($paquete / 6);
            $venta->ganancia1_mes4 = $afiliado - ($p1_mes4 / 6);
            $venta->ganancia2_mes4 = $afiliado - ($p2_mes4 / 6);
            $venta->precio_mes1 = $paquete / 6;
            $venta->precio1_mes4_calc = $p1_mes4 / 6;
            $venta->ganancia_paquete_mes1 = $venta->ganancia_mes1 * 6;
            $venta->ganancia1_paquete_mes4 = $venta->ganancia1_mes4 * 6;
            $venta->ganancia2_paquete_mes4 = $venta->ganancia2_mes4 * 6;
            $venta->ganancia_total_mes1 = ($venta->ganancia_mes1 * 6) * 2;
            $venta->ganancia_paquete_mes2 = $venta->ganancia_mes1 * 6;
            $venta->ganancia_total_mes2 = ($venta->ganancia_mes1 * 12) + $afiliado;
            $venta->ganancia_total_mes4 = $afiliado + $venta->ganancia1_paquete_mes4 + $venta->ganancia2_paquete_mes4;
        });
    }
}
