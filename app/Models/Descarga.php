<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Descarga extends Model
{
    protected $table = 'descargas';

    protected $fillable = [
        'url',
        'url_limpia',
        'tipo',
        'archivo',
        'exitosa',
        'error',
        'eliminado',
    ];

    protected $casts = [
        'exitosa' => 'boolean',
        'eliminado' => 'boolean',
    ];
}
