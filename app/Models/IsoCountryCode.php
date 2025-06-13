<?php // app/Models/IsoCountryCode.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IsoCountryCode extends Model
{
    use HasFactory;

    /**
     * La tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 't_iso2'; // Especifica el nombre exacto de tu tabla

    /**
     * La clave primaria para el modelo.
     *
     * @var string
     */
    protected $primaryKey = 'iso2'; // Tu clave primaria es 'iso2'

    /**
     * Indica si la clave primaria es autoincremental.
     *
     * @var bool
     */
    public $incrementing = false; // 'iso2' no es autoincremental

    /**
     * El "type" de la clave primaria autoincremental.
     *
     * @var string
     */
    protected $keyType = 'string'; // 'iso2' es char/string

    /**
     * Indica si el modelo debe tener timestamps.
     *
     * @var bool
     */
    public $timestamps = false; // Asumo que no tienes created_at/updated_at en t_iso2

    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'iso2',
        'pais',
    ];
}