<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use App\Models\Reserva;
use App\Models\Pasajero;
use App\Models\IsoCountryCode;

class EstanciasController extends Controller
{
    public function index(Request $request)
    {
        $pasajeroId = $request->input('pasajero_id', 1);
        $hasta = $request->filled('hasta')
            ? Carbon::parse($request->input('hasta'))->startOfDay()
            : Carbon::now()->startOfDay();

        $desde = $request->filled('desde')
            ? Carbon::parse($request->input('desde'))->startOfDay()
            : $hasta->copy()->subDays(365);

        $bloques = $this->calcularBloques($pasajeroId, $desde, $hasta);
        $estancias = $this->agruparPorPaisConNota($bloques);

        return response()->json([
            'pasajero_id' => $pasajeroId,
            'desde' => $desde->toDateString(),
            'hasta' => $hasta->toDateString(),
            'estancias' => $estancias,
            'bloques' => $bloques,
        ]);

    }

    public function pasajeros()
    {
        return Pasajero::orderBy('nombre_unificado')
            ->select('id', 'nombre_unificado')
            ->get();
    }

    public function cronograma(Request $request)
    {
        $dias = (int) $request->input('dias', 180);
        $desde = Carbon::now()->subDays($dias)->startOfDay();
        $hasta = Carbon::now();

        $reservas = Reserva::with('pasajero')
            ->where('fecha_fin', '>=', $desde)
            ->where('fecha_inicio', '<=', $hasta)
            ->get();

        $cronograma = [];

        foreach ($reservas as $registro) {
            $nombre = $registro->pasajero->nombre_unificado ?? 'Desconocido';
            $inicio = Carbon::parse($registro->fecha_inicio);
            $fin = Carbon::parse($registro->fecha_fin);

            for ($fecha = $inicio->copy(); $fecha <= $fin; $fecha->addDay()) {
                $cronograma[] = [
                    'fecha' => $fecha->toDateString(),
                    'pasajero' => $nombre,
                    'pais' => $registro->pais_destino,
                ];
            }
        }

        usort($cronograma, fn($a, $b) => strcmp($a['fecha'], $b['fecha']));

        return response()->json([
            'periodo_dias' => $dias,
            'total_dias_registrados' => count($cronograma),
            'cronograma' => $cronograma
        ]);
    }

    private function calcularBloques($pasajeroId, $desde, $hasta)
    {
        $reservas = Reserva::where('pasajero_id', $pasajeroId)
            ->where('tipo_reserva', 'vuelo')
            ->whereBetween('fecha_inicio', [$desde, $hasta->copy()->addDays(2)])
            ->whereNotNull('pais_origen')
            ->whereNotNull('pais_destino')
            ->orderBy('fecha_inicio')
            ->get()
            ->values();

        $bloques = [];

        $primeraReserva = $reservas->first();
        if ($primeraReserva && $primeraReserva->fecha_inicio->gt($desde)) {
            $paisInicial = $primeraReserva->pais_origen ?? $primeraReserva->pais_destino ?? 'Desconocido';
            $bloques[] = [
                'pais' => $paisInicial,
                'desde' => $desde->toDateString(),
                'hasta' => $primeraReserva->fecha_inicio->copy()->subDay()->toDateString(),
            ];
        }

        foreach ($reservas as $i => $r) {
            $siguiente = $reservas[$i + 1] ?? null;

            $bloques[] = [
                'pais' => $r->pais_origen,
                'desde' => $r->fecha_inicio->toDateString(),
                'hasta' => $r->fecha_inicio->toDateString(),
                'iso2' => $this->getIso($r->pais_origen),
            ];

            $inicio = $r->fecha_fin->copy()->startOfDay();
            $fin = $siguiente ? $siguiente->fecha_inicio->copy()->startOfDay() : $hasta;
            if ($fin < $inicio) $fin = $inicio;

            $bloques[] = [
                'pais' => $r->pais_destino,
                'desde' => $inicio->toDateString(),
                'hasta' => $fin->toDateString(),
                'iso2' => $this->getIso($r->pais_destino),
            ];
        }

        return $bloques;
    }

    private function agruparPorPaisConNota($bloques)
    {
        return collect($bloques)->groupBy('pais')->map(function ($items, $pais) {
            $fechas = $items->flatMap(function ($e) {
                if (empty($e['desde']) || empty($e['hasta'])) return [];
                return $this->getDiasEntre($e['desde'], $e['hasta']);
            })->filter() // ⚠️ Filtra elementos nulos
            ->sort()
            ->unique()
            ->values();

            if ($fechas->isEmpty()) return null;

            // Convertir fechas a string para trabajar sin errores
            $fechasStr = $fechas->map(fn($f) => $f instanceof Carbon ? $f->toDateString() : (string) $f);

            // Detectar huecos reales entre fechas
            $conHuecos = false;
            for ($i = 1; $i < $fechasStr->count(); $i++) {
                $anterior = Carbon::parse($fechasStr[$i - 1]);
                $actual = Carbon::parse($fechasStr[$i]);
                if ($actual->diffInDays($anterior) > 1) {
                    $conHuecos = true;
                    break;
                }
            }

            return [
                'pais' => $pais,
                'dias' => $fechasStr->count(),
                'desde' => $fechasStr->first(),
                'hasta' => $fechasStr->last(),
                'nota' => $conHuecos ? 'con huecos' : null,
                'iso2' => $this->getIso($pais),
            ];

        })->filter()->values();
    }

    private function getDiasEntre($desde, $hasta)
    {
        if (empty($desde) || empty($hasta)) return collect();

        $ini = Carbon::parse($desde)->startOfDay();
        $fin = Carbon::parse($hasta)->startOfDay();
        $dias = [];
        while ($ini <= $fin) {
            $dias[] = $ini->copy();
            $ini->addDay();
        }
        return collect($dias);
    }

    private function getIso($pais)
    {
        return strtolower(
            IsoCountryCode::where('pais_normalizado', Str::ascii(strtolower(str_replace(' ', '', $pais))))
                ->value('iso2') ?: 'un'
        );
        
    }


}