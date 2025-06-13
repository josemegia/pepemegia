<?php // app/Models/Reserva.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Reserva extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_origen',
        'pasajero_id',
        'tipo_reserva',
        'proveedor',
        'numero_vuelo', // <-- AÑADIDO
        'ciudad_origen', // <-- AÑADIDO
        'pais_origen', // <-- AÑADIDO
        'aeropuerto_origen_iata', // <-- AÑADIDO
        'fecha_inicio',
        'hora_inicio', // <-- AÑADIDO
        'ciudad_destino', // <-- RENOMBRADO DE 'ciudad'
        'pais_destino', // <-- RENOMBRADO DE 'pais'
        'aeropuerto_destino_iata', // <-- AÑADIDO
        'fecha_fin',
        'hora_fin', // <-- AÑADIDO
        'direccion', // Este campo puede que no aplique para vuelos, pero lo dejamos
        'numero_reserva',
        'precio',
        'moneda',
        'datos_adicionales',
        'contenido_email',
        'mensaje_id'
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'datos_adicionales' => 'array',
        'precio' => 'decimal:2'
    ];

    public function pasajero()
    {
        return $this->belongsTo(Pasajero::class);
    }

    // Scopes opcionales si los usas con joins o relaciones
    public function scopeUltimos6Meses($query)
    {
        return $query->where('fecha_inicio', '>=', Carbon::now()->subMonths(6));
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo_reserva', $tipo);
    }

    public function scopePorEmail($query, $email)
    {
        return $query->where('email_origen', $email);
    }
}
