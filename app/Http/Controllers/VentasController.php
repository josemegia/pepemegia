<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Helpers\CurrencyHelper;
use App\Models\Venta;

class VentasController extends Controller
{
    public function presentacion(Request $request): View
    {
        $availableCodes = Venta::distinct()->pluck('pais_iso2')->toArray();

        $countries_data = [];

        foreach ($availableCodes as $iso2) {
            $countries_data[$iso2] = CurrencyHelper::getFlagData($iso2);
        }

        $defaultCode = 'cr';
        $requestedDivisa = $request->input('divisa');

        if ($requestedDivisa && in_array($requestedDivisa, $availableCodes)) {
            session(['divisa' => $requestedDivisa]);
        }

        $currentDivisa = session('divisa', $defaultCode);
        if (!in_array($currentDivisa, $availableCodes)) {
            $currentDivisa = $defaultCode;
        }

        // 🔹 Ventas del país seleccionado
        $ventasSeleccionadas = Venta::where('pais_iso2', $currentDivisa)->get();

        // 🔹 Ventas de Costa Rica (referencia)
        $ventasCR = Venta::where('pais_iso2', 'cr')->get();

        // 🔹 Formateador especial para Costa Rica
        $formatterCR = new \NumberFormatter('es_CR', \NumberFormatter::CURRENCY);
        $formatterCR->setAttribute(\NumberFormatter::FRACTION_DIGITS, 0); // sin decimales
        $formatterCR->setSymbol(\NumberFormatter::MONETARY_GROUPING_SEPARATOR_SYMBOL, ' '); // separador miles = espacio

        // 🔹 Campos numéricos a procesar
        $numericFields = [
            'precio_afiliado', 'precio_tienda', 'pvp',
            'precio2_paquete_mes4', 'precio1_paquete_mes4', 'propuesta_mensual', 'precio_paquete',
            'ganancia_mes1', 'ganancia1_mes4', 'ganancia2_mes4',
            'precio_mes1', 'precio1_mes4_calc',
            'ganancia_paquete_mes1', 'ganancia1_paquete_mes4', 'ganancia2_paquete_mes4',
            'ganancia_total_mes1', 'ganancia_paquete_mes2', 'ganancia_total_mes2', 'ganancia_total_mes4'
        ];

        // 🔹 Construir equivalencias
        $equivalencias = [];

        foreach ($numericFields as $field) {
            foreach ($ventasCR as $index => $ventaCR) {
                $ventaSeleccionada = $ventasSeleccionadas[$index] ?? null;
                if ($ventaSeleccionada) {
                    // Costa Rica → símbolo ₡ con espacio y miles separados por espacio
                    $valorCR = $formatterCR->formatCurrency($ventaCR->$field, 'CRC');

                    // País seleccionado → usando CurrencyHelper
                    $valorSel = CurrencyHelper::divisaBruta($ventaSeleccionada->$field, $currentDivisa);

                    $equivalencias[str_replace('₡','₡ ',$valorCR)] = $valorSel;
                }
            }
        }
        
        return view('ventas.index', [
            'divisa'        => $currentDivisa,
            'paises'        => $countries_data,
            'ventas'        => $ventasSeleccionadas,
            'equivalencias' => $equivalencias,
        ]);
    }
    
    public function form(Request $request): View
    {
        $divisasConfig = config('menu.divisas');
        
        $selectedPaisIso = $request->input('pais_iso2', 'cr');
        if (!array_key_exists($selectedPaisIso, $divisasConfig)) {
            $selectedPaisIso = 'cr'; // País por defecto si el solicitado no es válido
        }

        $ventasData = [];

        foreach ($divisasConfig as $iso => $config) {
            $venta = Venta::where('pais_iso2', $iso)->first();

            // Construimos el array de datos directamente desde la configuración
            $ventasData[$iso] = [
                'idioma'          => $config['idioma'] ?? 'es_XX',
                'allowDecimals'   => $config['dec'] ?? true,
                'currencyCode'    => $config['code'] ?? 'USD',
                'currencySymbol'  => $config['symbol'] ?? '$',
                'fields' => $venta ? $venta->toArray() : [
                    'precio_afiliado'       => '', 'precio_tienda'          => '', 'pvp' => '',
                    'precio2_paquete_mes4'  => '', 'precio1_paquete_mes4'   => '',
                    'propuesta_mensual'     => '', 'precio_paquete'         => '',
                ]
            ];
        }

        return view('ventas.form', [
            'pais'      => $selectedPaisIso,
            'divisas'   => $divisasConfig,
            'ventasData'=> $ventasData,
        ]);
    }

    public function storeOrUpdate(Request $request)
    {
        $validated = $request->validate([
            'pais_iso2' => 'required|string|max:5',
            'idioma' => 'required|string|max:10',
            'precio_afiliado' => 'required|numeric',
            'precio_tienda' => 'required|numeric',
            'pvp' => 'required|numeric',
            'precio2_paquete_mes4' => 'required|numeric',
            'precio1_paquete_mes4' => 'required|numeric',
            'propuesta_mensual' => 'required|numeric',
            'precio_paquete' => 'required|numeric',
        ]);

        Venta::updateOrCreate(
            ['pais_iso2' => $validated['pais_iso2'], 'idioma' => $validated['idioma']],
            $validated
        );

        return redirect()->route('ventas.form', ['pais_iso2' => $validated['pais_iso2']])
                         ->with('success', 'Registro guardado correctamente.');
    }
}
