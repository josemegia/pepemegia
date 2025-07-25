<?php // app/Models/Pasajero.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pasajero extends Model
{
    use HasFactory;

    protected $table = 'pasajeros';

    protected $fillable = [
        'nombre_original',
        'nombre_unificado',
        'variantes',
    ];

    protected $casts = [
        'variantes' => 'array',
    ];
    
    public function reservas()
    {
        return $this->hasMany(Reserva::class);
    }
}
