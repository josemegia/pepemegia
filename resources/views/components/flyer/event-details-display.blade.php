@props(['data' => []])

<div class="event-details">
@php
    $ctaLink = $data['cta']['link'] ?? null;
    $mensajeDecodificado = null;

    if ($ctaLink && str_contains($ctaLink, 'api.whatsapp.com')) {
        parse_str(parse_url($ctaLink, PHP_URL_QUERY), $query);
        $mensajeDecodificado = isset($query['text']) ? urldecode($query['text']) : null;
    }
@endphp

<p><strong>Enlace:</strong>
    @if($ctaLink)
        <a href="{{ $ctaLink }}" target="_blank" rel="noopener noreferrer">Abrir WhatsApp</a>
    @else
        No especificado
    @endif
</p>

@if($mensajeDecodificado)
    <div class="bg-gray-800 text-white text-sm p-4 rounded mt-2 whitespace-pre-line border border-gray-600">
        {!! nl2br(e($mensajeDecodificado)) !!}
    </div>
@endif


    <h3>Detalles del Evento</h3>
    <p><strong>Fecha y Hora:</strong> {{ $data['event']['date'] ?? 'No especificado' }} a las {{ $data['event']['time'] ?? 'No especificado' }}</p>
    @if($data['event']['phone_international'] ?? false)
        <p><strong>Teléfono:</strong> {{ $data['event']['phone_international'] }}</p>
    @endif
    <p><strong>Plataforma:</strong> {{ $data['event']['platform'] ?? 'No especificado' }}</p>
    <p><strong>Detalles:</strong> {{ $data['event']['platform_details'] ?? 'No especificado' }}</p>
    <p><strong>Enlace:</strong> <a href="{{ $data['cta']['link'] ?? '#' }}">{{ $data['cta']['button_text'] ?? 'Unirse' }}</a></p>
</div>