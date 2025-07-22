@if (!($is_shared_view ?? false))

    <title>@yield('page_title_format_display',
                    ($data['event']['date'] ?? '') . ' ' . 
                    ($data['event']['time'] ?? '') . ' ' . 
                    (config('flyer.formats.' . $current_format_name . '.name', 'Flyer WhatsApp') ?? ''))</title>

    <link rel="manifest" href="{{ asset('flyer-manifest.json') }}">
    <meta name="theme-color" content="#007bff">

    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Flyer">

    @foreach ([72, 96, 128, 144, 152, 192, 384, 512] as $size)
        <link rel="apple-touch-icon" sizes="{{ $size }}x{{ $size }}" href="{{ asset("storage/icons/flyer/icon-{$size}x{$size}.png") }}">
    @endforeach
@else

    <title>@yield('page_title_format_display',
                    ($data['speaker']['name'] ?? '') . ' ' . 
                    ($data['event']['date'] ?? '') . ' ' . 
                    ($data['event']['time'] ?? '') . ' ' . 
                    ($data['event']['platform'] ?? '') . ' ' . 
                    ($data['event']['platform_details'] ?? ''). ' ' . 
                    (config('flyer.formats.' . $current_format_name . '.name', 'Flyer WhatsApp') ?? ''))</title>

    <meta property="og:title" content="{{
                    ($data['speaker']['name'] ?? '') . ' ' .
                    (\Carbon\Carbon::parse($data['event']['date'])->translatedFormat('j \de F') ?? '') . ' ' . 
                    (\Carbon\Carbon::parse($data['event']['time'])->format('ga') ?? '') . ' ' .
                    ($data['event']['platform'] ?? '') . ' ' . 
                    ($data['event']['platform_details'] ?? '') }}">
    <meta property="og:description" content="{{ $data['mainTitle'] ?? '' }} ¡Es importante! {{$data['subtitle'] ?? '' }}. {{ $data['cta']['footer_text'] ?? '' }}">
    
    {{-- La URL completa de la imagen de vista previa. `asset()` lo hace por ti. --}}
    <meta property="og:image" content="{{ asset('storage/flyers/' . $data['speaker']['image']) }}"> {{-- asset("storage/flyers/shared/{$uuid}/{$filename}.png") --}}
    
    {{-- La URL canónica de la página que estás compartiendo. --}}
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="website">

@endif
