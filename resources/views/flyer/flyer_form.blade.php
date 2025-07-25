{{-- resources/views/flyer/flyer_form.blade.php --}}

{{-- 1. Extendemos del layout principal. Le pasamos un tema por defecto para que no falle. --}}
@extends('flyer.flyer_pwa', ['theme' => config('flyer.themes.corporate')])

{{-- 2. Definimos el título de la página --}}
@section('page_title_format_display', 'Editar Datos del Flyer')

{{-- 3. Todo el contenido del formulario va dentro de la sección 'content' --}}
@section('content')
<div class="w-full max-w-2xl bg-gray-800 p-8 rounded-2xl shadow-2xl text-white">

    <h1 class="text-3xl font-bold mb-6 text-center">Actualizar Datos del Flyer</h1>
    
    <button type="submit" form="flyer-form" class="w-full text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-800 font-medium rounded-lg text-sm px-5 py-3 text-center mb-6 transition-colors duration-300">Guardar Cambios y Ver Flyer</button>

    <x-flyer.alert-messages />

    <form action="{{ route('flyer.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6" id="flyer-form">
        @csrf

	    <x-flyer.format-selector :formats="$formats" :selected="old('flyer_format', $data['format'] ?? config('flyer.default_format'))" />        

        <x-flyer.main-content-fields :data="$data" />

        <x-flyer.speaker-fields :data="$data" />

        <x-flyer.event-details-fields
            :data="$data"
            :regions="$regions"
            :region="$defaultregion"
        />

        <x-flyer.event-details-display :data="$data" />
        
        <button type="submit" class="w-full text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-800 font-medium rounded-lg text-sm px-5 py-3 text-center mt-6 transition-colors duration-300">Guardar Cambios y Ver Flyer</button>
    </form>
    
    <div class="text-center mt-6"><a href="{{ route('flyer.show') }}" class="text-blue-400 hover:underline text-sm">Volver al Flyer sin guardar</a></div>

</div>

@push('scripts')
<script>
    // JavaScript para actualizar el campo oculto cuando el usuario selecciona un archivo
    document.getElementById('speaker_image').addEventListener('change', function() {
        if (this.files.length > 0) {
            document.getElementById('speaker_image_sent').value = '1';
        } else {
            document.getElementById('speaker_image_sent').value = '0';
        }
    });

    // Lógica para deshabilitar campos al cambiar el formato
    document.addEventListener('DOMContentLoaded', function() {
        const flyerFormatSelect = document.getElementById('flyer_format');
        const mainContentFields = document.getElementById('mainContentFields');
        const speakerFields = document.getElementById('speakerFields');
        const eventDetailsFields = document.getElementById('eventDetailsFields');
        const flyerForm = document.getElementById('flyer-form'); // Obtener referencia al formulario

        // Función para deshabilitar/habilitar campos
        function toggleFieldsDisabled(disabled) {
            const fieldsets = [mainContentFields, speakerFields, eventDetailsFields];
            fieldsets.forEach(fieldset => {
                if (fieldset) { // Asegurarse de que el fieldset exista
                    const inputs = fieldset.querySelectorAll('input, textarea, select');
                    inputs.forEach(input => {
                        input.disabled = disabled;
                        // Opcional: Cambiar estilo para indicar que está deshabilitado
                        if (disabled) {
                            input.classList.add('opacity-50', 'cursor-not-allowed');
                        } else {
                            input.classList.remove('opacity-50', 'cursor-not-allowed');
                        }
                    });
                }
            });
        }

        // Guardar el formato inicial del flyer
        const initialFlyerFormat = flyerFormatSelect.value;

        // Escuchar cambios en el selector de formato
        flyerFormatSelect.addEventListener('change', function() {
            // Si el formato seleccionado es diferente al inicial, deshabilitar campos y enviar formulario
            if (this.value !== initialFlyerFormat) {
                // Antes de enviar el formulario, re-habilitamos los campos para que sus valores se incluyan en la petición
                toggleFieldsDisabled(false); // Importante: deshabilitar = false para re-habilitar
                flyerForm.submit(); // Enviar el formulario automáticamente
            } else {
                // Si el usuario vuelve al formato inicial, re-habilitar campos
                toggleFieldsDisabled(false);
            }
        });

        // Opcional: Si quieres que los campos se deshabiliten al cargar la página si el formato no es el inicial
        // Por ejemplo, si el formato inicial cargado NO es 'standard' y 'standard' es el único editable
        // if (initialFlyerFormat !== 'standard') {
        //     toggleFieldsDisabled(true);
        // }
    });
</script>
@endpush

@endsection
