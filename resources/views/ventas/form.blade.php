@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto bg-white shadow-lg rounded-lg p-8 mt-10"
     x-data="ventasForm()"
     x-init='init(
        "{{ $pais }}",
        @json($ventasData)
     )'>

    <h2 class="text-2xl font-bold mb-6">Configuración de Ventas</h2>

    @if(session('success'))
        <div class="bg-green-100 text-green-800 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('ventas.storeOrUpdate') }}" class="space-y-6">
        @csrf

        <input type="hidden" name="pais_iso2" :value="selectedPais">
        <input type="hidden" name="idioma" :value="currentIdioma">

        <div class="mb-6 grid grid-cols-2 gap-4">
            <div>
                <label class="font-semibold">País</label>
                <select name="pais_selector"
                        x-model="selectedPais"
                        @change="onCountryChange()"
                        class="w-full border-gray-300 rounded-lg">
                    @foreach($divisas as $iso => $data)
                        <option value="{{ $iso }}">{{ strtoupper($iso) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="font-semibold">Idioma</label>
                <input type="text"
                       class="w-full border-gray-300 rounded-lg bg-gray-100"
                       :value="currentIdioma"
                       readonly>
            </div>
        </div>

        @php
            $baseFields = [
                'precio_afiliado' => 'Precio Afiliado',
                'precio_tienda' => 'Precio Tienda',
                'pvp' => 'PVP',
                'precio2_paquete_mes4' => 'Precio2 Paquete Mes4',
                'precio1_paquete_mes4' => 'Precio1 Paquete Mes4',
                'propuesta_mensual' => 'Propuesta Mensual',
                'precio_paquete' => 'Precio Paquete',
            ];
        @endphp

        <div class="grid grid-cols-2 gap-4">
            @foreach($baseFields as $name => $label)
                <div>
                    <label class="font-semibold flex items-center gap-1">
                        {{ $label }}
                        <span x-text="moneda" class="text-gray-500 font-mono"></span>
                    </label>
                    <input
                        type="number"
                        :step="allowDecimals ? '0.01' : '1'"
                        name="{{ $name }}"
                        x-model="formFields.{{ $name }}"
                        required
                        @keydown="if (!allowDecimals && ($event.key === '.' || $event.key === ',')) $event.preventDefault();"
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring focus:ring-blue-200">
                </div>
            @endforeach
        </div>

        <div class="flex justify-end pt-4">
            <button type="submit"
                    class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
                Guardar Cambios
            </button>
        </div>
    </form>
</div>

<script>
function ventasForm() {
    return {
        selectedPais: '',
        allVentasData: {},
        formFields: {},
        currentIdioma: '',
        allowDecimals: true,
        moneda: '',

        init(initialPais, data) {
            this.allVentasData = data;
            if (this.allVentasData && this.allVentasData[initialPais]) {
                this.selectedPais = initialPais;
            } else if (this.allVentasData) {
                this.selectedPais = Object.keys(this.allVentasData)[0];
            }
            this.updateFormForCurrentCountry();
        },

        onCountryChange() {
            this.updateFormForCurrentCountry();
        },

        updateFormForCurrentCountry() {
            if (!this.allVentasData || !this.allVentasData[this.selectedPais]) {
                console.error('Datos no encontrados para el país:', this.selectedPais);
                return;
            }
            const countryData = this.allVentasData[this.selectedPais];
            const dbFields = countryData.fields;

            // 1. Establecemos la regla de decimales ANTES de formatear los números.
            this.allowDecimals = countryData.allowDecimals;

            // 2. Creamos una función de ayuda para formatear los números
            const formatValue = (value) => {
                if (!this.allowDecimals && (value || value === 0)) {
                    const num = parseFloat(value); 
                    return isNaN(num) ? '' : Math.round(num);
                }
                return value;
            };

            // 3. Aplicamos la función de formato a cada campo
            this.formFields = {
                precio_afiliado:      formatValue({{ json_encode(old('precio_afiliado')) }} ?? dbFields.precio_afiliado),
                precio_tienda:        formatValue({{ json_encode(old('precio_tienda')) }} ?? dbFields.precio_tienda),
                pvp:                  formatValue({{ json_encode(old('pvp')) }} ?? dbFields.pvp),
                precio2_paquete_mes4: formatValue({{ json_encode(old('precio2_paquete_mes4')) }} ?? dbFields.precio2_paquete_mes4),
                precio1_paquete_mes4: formatValue({{ json_encode(old('precio1_paquete_mes4')) }} ?? dbFields.precio1_paquete_mes4),
                propuesta_mensual:    formatValue({{ json_encode(old('propuesta_mensual')) }} ?? dbFields.propuesta_mensual),
                precio_paquete:       formatValue({{ json_encode(old('precio_paquete')) }} ?? dbFields.precio_paquete),
            };
            
            this.currentIdioma = countryData.idioma;
            this.actualizarMoneda(countryData.currencyCode, countryData.currencySymbol); 
        },

        actualizarMoneda(code, symbol) {
            if (code && symbol) {
                this.moneda = `(${code} ${symbol})`;
            } else {
                this.moneda = '($)'; // Fallback por si acaso
            }
        }
    }
}
</script>
@endsection