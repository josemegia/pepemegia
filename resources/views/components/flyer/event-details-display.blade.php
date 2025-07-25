@props(['data' => []])

<div class="event-details">
    <h3>Detalles del Evento</h3>
    <p><strong>Fecha y Hora:</strong> {{ $data['event']['date'] ?? 'No especificado' }} a las {{ $data['event']['time'] ?? 'No especificado' }}</p>
    @if($data['event']['phone_international'] ?? false)
        <p><strong>Teléfono:</strong> {{ $data['event']['phone_international'] }}</p>
    @endif
    <p><strong>Plataforma:</strong> {{ $data['event']['platform'] ?? 'No especificado' }}</p>
    <p><strong>Detalles:</strong> {{ $data['event']['platform_details'] ?? 'No especificado' }}</p>
    <p><strong>Enlace:</strong> <a href="{{ $data['cta']['link'] ?? '#' }}">{{ $data['cta']['button_text'] ?? 'Unirse' }}</a></p>
</div>