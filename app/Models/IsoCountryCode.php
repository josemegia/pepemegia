<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IsoCountryCode extends Model
{
    use HasFactory;

    protected $table = 't_iso2';
    protected $primaryKey = 'iso2';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'iso2',
        'iso3',
        'pais',
        'lang', // <-- Se añade el nuevo campo aquí
        'counter',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'counter' => 'integer',
    ];

    /**
     * Convierte un código ISO3 a ISO2.
     *
     * @param string $iso3
     * @return string|null
     */
    public static function iso3toIso2($iso3)
    {
        return static::where('iso3', strtolower($iso3))->value('iso2');
    }
    
    /**
     * Convierte un código ISO2 a ISO3.
     *
     * @param string $iso2
     * @return string|null
     */
    public static function iso2toIso3($iso2)
    {
        return static::where('iso2', strtolower($iso2))->value('iso3');
    }

    /**
     * Obtiene el código de idioma principal (ISO 639-1) para un país dado su código ISO2.
     *
     * @param string $iso2 El código de país de 2 letras (ej. 'ES', 'GR').
     * @return string|null El código de idioma de 2 letras (ej. 'es', 'el') o null si no se encuentra.
     */
    public static function getLangForIso2($iso2)
    {
        // Busca el registro por su llave primaria (iso2) y devuelve el valor de la columna 'lang'.
        // Usamos find() porque es más eficiente para buscar por llave primaria.
        return static::find(strtoupper($iso2))?->lang;
    }
}