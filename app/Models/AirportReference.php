<?php // app/Models/AirportReference.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AirportReference extends Model
{
    use HasFactory;

    protected $fillable = [
        'identifier_type',
        'identifier_value',
        'country_name',
    ];

    protected $table = 'airport_references'; // Especifica el nombre de la tabla si no sigue la convención
}