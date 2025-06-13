<?php

namespace App\Services;

use App\Models\Pasajero;
use App\Models\Reserva;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ReservationRegistrar
{
    public function guardar(array $reservaData, array $segmentos, Pasajero $pasajero, string $emailOrigen, ?string $mensajeId = null, ?string $contenidoEmail = null): Collection
    {
        $reservas = collect();

        foreach ($segmentos as $segmento) {
            $existe = Reserva::where('pasajero_id', $pasajero->id)
                ->where('numero_vuelo', $segmento['numero_vuelo'] ?? null)
                ->where('fecha_inicio', $segmento['fecha_salida'] ?? null)
                ->exists();

            if ($existe) {
                Log::warning("Reserva duplicada detectada. No se registró. Detalles:", [
                    'pasajero_id'     => $pasajero->id,
                    'numero_vuelo'    => $segmento['numero_vuelo'] ?? null,
                    'fecha_salida'    => $segmento['fecha_salida'] ?? null,
                    'fecha_llegada'   => $segmento['fecha_llegada'] ?? null,
                ]);
                continue;
            }

            $reserva = Reserva::create([
                'email_origen'             => $emailOrigen,
                'pasajero_id'              => $pasajero->id,
                'tipo_reserva'             => $reservaData['tipo_reserva'] ?? 'vuelo',
                'proveedor'                => $reservaData['proveedor'] ?? null,
                'numero_vuelo'             => $segmento['numero_vuelo'] ?? null,
                'ciudad_origen'            => $segmento['ciudad_origen'] ?? null,
                'pais_origen'              => $segmento['pais_origen'] ?? null,
                'aeropuerto_origen_iata'   => $segmento['aeropuerto_origen_iata'] ?? null,
                'fecha_inicio'             => $segmento['fecha_salida'] ?? null,
                'hora_inicio'              => $segmento['hora_salida'] ?? null,
                'ciudad_destino'           => $segmento['ciudad_destino'] ?? null,
                'pais_destino'             => $segmento['pais_destino'] ?? null,
                'aeropuerto_destino_iata'  => $segmento['aeropuerto_destino_iata'] ?? null,
                'fecha_fin'                => $segmento['fecha_llegada'] ?? null,
                'hora_fin'                 => $segmento['hora_llegada'] ?? null,
                'direccion'                => null,
                'numero_reserva'           => $reservaData['numero_reserva'] ?? null,
                'precio'                   => $reservaData['precio'] ?? null,
                'moneda'                   => $reservaData['moneda'] ?? null,
                'datos_adicionales'        => [
                    'numero_billete'         => $reservaData['datos_adicionales']['numero_billete'] ?? null,
                    'fecha_emision_billete' => $reservaData['datos_adicionales']['fecha_emision_billete'] ?? null,
                    'clase_tarifa'          => $segmento['clase_tarifa'] ?? null,
                    'franquicia_equipaje'   => $segmento['franquicia_equipaje'] ?? null,
                    'estado'                => $segmento['estado'] ?? null,
                    'terminal_salida'       => $segmento['terminal_salida'] ?? null,
                    'terminal_llegada'      => $segmento['terminal_llegada'] ?? null
                ],
                'contenido_email'          => $contenidoEmail,
                'mensaje_id'               => $mensajeId,
            ]);

            $reservas->push($reserva);
        }

        return $reservas;
    }
}
