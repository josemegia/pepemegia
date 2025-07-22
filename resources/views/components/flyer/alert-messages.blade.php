{{-- resources/views/components/flyer/alert-messages.blade.php --}}

@props(['isSharedView' => false])

{{-- Mensajes de Éxito --}}
@if (session('success'))
    <div class="w-full max-w-lg bg-green-800 border border-green-600 text-white p-4 rounded-lg mb-4 mx-auto">
        <p class="font-bold text-center mb-2">¡Éxito!</p>
        <p class="text-sm text-center">{{ session('success') }}</p>
    </div>
@endif

{{-- Mensajes de Advertencia (ej. para fallos de subida por PHP.ini) --}}
@if (session('warning'))
    <div class="w-full max-w-lg bg-orange-800 border border-orange-600 text-white p-4 rounded-lg mb-4 mx-auto">
        <p class="font-bold text-center mb-2">Advertencia:</p>
        <p class="text-sm text-center">{{ session('warning') }}</p>
    </div>
@endif

{{-- Mensaje de éxito con enlace compartido (solo para la vista principal del admin) --}}
@if(!$isSharedView && session('shared_link'))
    <div class="w-full max-w-lg bg-green-800 border border-green-600 text-white p-4 rounded-lg mb-4 mx-auto">
        <p class="font-bold text-center mb-2">¡Enviar por WhatsApp u otra App!</p>
        <p class="text-sm mb-2 text-center text-green-200">Usa este enlace para compartir:</p>
        <div class="flex items-center space-x-2">
            <input type="text" id="sharedLinkInput" value="{{ session('shared_link') }}"
                class="w-full bg-green-900 text-green-100 p-2 rounded border border-green-700" readonly>
            <button id="copyLinkBtn"                    
                    class="bg-green-600 hover:bg-green-500 text-white font-bold p-2 rounded"
                    title="Copiar enlace">
                <x-icons.copy class="h-5 w-5" />
            </button>
        </div>
    </div>
    {{-- El script JS para copyLinkBtn se moverá a flyer.js --}}
@endif

{{-- Mensajes de Error de Validación --}}
@if ($errors->any())
    <div class="w-full max-w-lg bg-red-900 border border-red-700 text-red-200 p-4 rounded-lg mb-6 mx-auto">
        <p class="font-bold mb-2 text-center">Por favor, corrige los siguientes errores:</p>
        <ul class="list-disc list-inside text-sm text-left">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
