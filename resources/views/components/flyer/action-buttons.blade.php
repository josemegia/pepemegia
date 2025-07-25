{{-- resources/views/components/flyer/action-buttons.blade.php --}}

@props(['isSharedView' => false, 'uuid' => null, 'filename' => null, 'data' => null, 'current_format_name' => null])

<div id="buttons-wrapper" class="flex justify-center items-center space-x-4 mb-4" data-confirm-shared-url="{{ route('flyer.confirmShared') }}">

    @if($isSharedView)
    
        {{-- Botón para Descargar la imagen pre-generada --}}
        <div class="fixed top-4 right-4 z-50">
            <a href="{{ asset("storage/flyers/shared/{$uuid}/{$filename}.png") }}"
            download="flyer-{{ ((($data['speaker']['name'] ?? '') . ' ' . ($data['event']['date'] ?? '') . ' ' . ($data['event']['time'] ?? '')) ?? 'invitacion') }}.png"
            class="bg-blue-600 text-white p-3 rounded-lg shadow-md hover:bg-blue-700 transition-colors duration-300 flex items-center justify-center"
            title="Descargar flyer">
                
                {{-- Icono de Descarga --}}
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
            </a>
        </div>

    @else

        {{-- Botón Capturar --}}
        <button id="captureBtn"
                class="bg-blue-600 text-white p-3 rounded-lg shadow-md hover:bg-blue-700 transition-colors duration-300 flex items-center justify-center"
                title="Capturar flyer">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <span id="captureBtnText" class="sr-only"></span>
        </button>

        {{-- Botón Editar --}}
        <a href="{{ route('flyer.edit') }}"
        class="bg-green-600 text-white p-3 rounded-lg shadow-md hover:bg-green-700 transition-colors duration-300 flex items-center"
        title="Editar flyer">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.536L16.732 3.732z"/>
            </svg>
        </a>

        {{-- Contenedor del botón Nuevo Flyer (inicialmente oculto si no se ha compartido) --}}
        <div id="new-flyer-button-container"
             @unless (session('flyer_was_shared')) style="display: none;" @endunless>
            <a href="{{ route('flyer.new') }}"
               class="bg-orange-500 text-white font-semibold py-2 px-4 rounded-lg shadow-md hover:bg-orange-600 transition-colors duration-300 flex items-center">
                <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 4v16m8-8H4"/>
                </svg>
            </a>
        </div>

        {{-- Botón Refrescar --}}
        <a href="{{ route('flyer.show') }}"
           class="bg-yellow-500 text-white p-3 rounded-lg shadow-md hover:bg-yellow-600 transition-colors duration-300 flex items-center"
           title="Cambiar tema">
            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd"
                      d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z"
                      clip-rule="evenodd"/>
            </svg>
        </a>
      @if (isset($current_format_name))
        <a href="{{ route('flyer.nextFormat', ['current' => $current_format_name]) }}"
            class="bg-purple-600 text-white p-3 rounded-lg shadow-md hover:bg-purple-700 transition-colors duration-300 flex items-center"
            title="Cambiar formato">
            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd"
                    d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z"
                    clip-rule="evenodd"/>
            </svg>
        </a>
      @endif
    @endif
</div>