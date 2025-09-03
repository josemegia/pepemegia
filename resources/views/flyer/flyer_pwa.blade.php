<!DOCTYPE html>
{{-- /resources/views/flyer/flyer_pwa.blade.php --}}
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <meta name="csrf-token" content="{{ csrf_token() }}">
   
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/flyer.js'])

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="{{ $theme['font_link'] ?? 'https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap' }}" rel="stylesheet">
    <link href="{{ $theme['font_link'] ?? 'https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap' }}" rel="stylesheet">

    @include('components.flyer.is_shared')

    @yield('format_specific_styles') 

</head>
<body>
    <div class="bg-gray-900 flex flex-col items-center justify-center min-h-screen p-4 {{ $theme['font_family_class'] ?? 'font-sans' }}">
        <div class="text-white w-full max-w-4xl mx-auto">
            {{-- Mensaje de éxito solo para admins --}}
            <x-flyer.alert-messages     :is-shared-view="$is_shared_view" />

            {{-- Botones adaptados al contexto --}}
            <x-flyer.action-buttons
                :is_shared_view="$is_shared_view"
                :uuid="$uuid ?? null"
                :filename="$filename ?? null"
                :data="$data ?? null"
                :current_format_name="$current_format_name"
            />

            {{-- Contenido del flyer --}}
            @yield('content')

            {{-- Controles de reinicio (solo para admins) --}}
            <x-flyer.restore-controls   :is-shared-view="$is_shared_view" />

        </div>

    </div>

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/flyer-sw.js', { scope: '/flyer/' })
                    .then(registration => {
                        console.log('Service Worker registrado...');
                    })
                    .catch(error => {
                        console.error('Fallo el registro del Service Worker:', error);
                    });
            });
        }
    </script>
</body>
</html>