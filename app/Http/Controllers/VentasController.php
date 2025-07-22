<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\App;

use App\Helpers\CurrencyHelper;

class VentasController extends Controller
{
    
    public function show(Request $request, string $dia): View|RedirectResponse
    {
        $diapositivas = ['1'];
        if (!in_array($dia, $diapositivas))
            return redirect()->route('plan.ventas.show', ['dia' => '1'], ['divisa' => 'co']);

        $divisasConfig = config('menu.divisas');
        $availableCodes = array_keys($divisasConfig);
        foreach ($availableCodes as $iso2) $countries_data[$iso2] = CurrencyHelper::getFlagData($iso2);
        $defaultCode = 'es';

        $requestedDivisa = $request->input('divisa');
        if ($requestedDivisa && in_array($requestedDivisa, $availableCodes)) 
            session(['divisa' => $requestedDivisa]);

        $currentDivisa = session('divisa', $defaultCode);
        if (!in_array($currentDivisa, $availableCodes))
            $currentDivisa = $defaultCode;

        $cambio = $divisasConfig[$currentDivisa]['cambio'];

        $indiceActual = array_search($dia, $diapositivas);
        $anterior = $diapositivas[$indiceActual - 1] ?? end($diapositivas);
        $siguiente = $diapositivas[$indiceActual + 1] ?? $diapositivas[0];
        
        return view('ventas.show', [
            'dia' => $dia,
            'anterior' => $anterior,
            'siguiente' => $siguiente,
            'divisa' => $currentDivisa,
            'cambio' => $cambio,
            'divisas' => $divisasConfig,
            'paises' => $countries_data,
        ]);
    }


}
