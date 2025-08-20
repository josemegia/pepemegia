@component('mail::message')
# {{ $data['mainTitle'] ?? 'Reunión Zoom' }}

Hola {{ $data['speaker']['name'] ?? 'anfitrión/a' }},

Tu reunión ha sido creada exitosamente. Aquí tienes los enlaces importantes:

---

## 📅 Detalles del evento
- **Título:** {{ $data['mainTitle'] }}
- **Subtítulo:** {{ $data['subtitle'] }}
- **Fecha:** {{ $data['event']['date'] }}
- **Hora:** {{ $data['event']['time'] }}
- **Plataforma:** {{ $data['event']['platform'] }}
@if (!empty($data['event']['platform_details']))
- **Detalles:** {{ $data['event']['platform_details'] }}
@endif

---

## 🎥 Enlace para invitados
@component('mail::button', ['url' => $data['guest']['link']])
Unirse a la reunión
@endcomponent

## 👤 Enlace para el anfitrión
@component('mail::button', ['url' => $data['admin']['link'], 'color' => 'red'])
Iniciar como anfitrión
@endcomponent

Gracias por usar {{ config('app.name') }}.

@endcomponent
