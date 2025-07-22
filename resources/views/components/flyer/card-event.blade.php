{{-- resources/views/components/flyer/card-event.blade.php --}}
@props(['data' => []])

@php
    $event = $data['event'] ?? [];
    $cta = $data['cta'] ?? [];
@endphp

<div class="event-details-note">
    <p><strong>📅 Fecha:</strong> {{ $event['date'] ?? '' }}</p>
    <p><strong>⏰ Hora:</strong> {{ $event['time'] ?? '' }}</p>
    <p><strong>📍 Plataforma:</strong> {{ $event['platform'] ?? '' }}</p>
    <p><strong>ID:</strong> {{ $event['platform_details'] ?? '' }}</p>
</div>

<a href="{{ $cta['link'] ?? '#' }}" class="cta-button-scribble">
    ✍️ ¡QUIERO PARTICIPAR!
</a>
