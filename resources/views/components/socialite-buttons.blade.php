@php
    $providers = collect(config('socialite'))
        ->reject(function ($value, $provider) {
            // Si está comentado en config/socialite.php, no llega aquí
            // Si por algún motivo quieres filtrar por config/env, hazlo aquí
            return false;
        });
@endphp

@foreach ($providers as $provider => $info)
    <div class="mb-2">
        <a href="{{ route('socialite.redirect', $provider) }}"
           class="w-full inline-flex items-center justify-center gap-2 bg-white text-gray-800 border border-gray-300 rounded py-2 px-4 shadow hover:bg-gray-100 transition font-semibold">
            <i class="{{ $info['icon'] }}"></i>
            {{ $info['label'] }}
        </a>
    </div>
@endforeach

@if ($providers->count())
    <div class="flex items-center my-6">
        <div class="flex-grow border-t border-gray-300"></div>
        <span class="mx-4 text-gray-500">{{ __('or') }}</span>
        <div class="flex-grow border-t border-gray-300"></div>
    </div>
@endif
