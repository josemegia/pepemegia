@props(['isSharedView' => false])

@if(!$isSharedView)
    <div class="flex justify-center items-center space-x-4 mt-4">
        <a href="{{ route('flyer.reset') }}" class="text-red-400 hover:underline text-sm">Reiniciar</a>
        <span class="text-gray-500">|</span>
        <a href="{{ route('flyer.restore') }}" class="text-blue-400 hover:underline text-sm">Volver al formato por defecto</a>
    </div>
@endif
    <br><br><br><br><br>

