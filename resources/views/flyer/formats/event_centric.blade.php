@extends('flyer.flyer_pwa')

@section('google_font_family_url', 'https://fonts.googleapis.com/css2?family=Pacifico&family=Patrick+Hand&family=Permanent+Marker&family=Indie+Flower&family=Architects+Daughter&display=swap')
@section('css_font_family', 'font-permanent-marker')

@section('format_specific_styles')
    <style>
        body {
            background-color: #2c3e50;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 2rem 0;
            overflow-x: hidden;
        }

        .flyer-canvas {
            position: relative;
            width: 100%;
            max-width: 900px;
            min-height: 550px;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background-color: rgba(0,0,0,0.1);
            border-radius: 1rem;
            box-shadow: inset 0 0 15px rgba(0,0,0,0.3);
            gap: 2rem;
        }

        @media (min-width: 768px) {
            .flyer-canvas {
                flex-direction: row;
                justify-content: space-around;
                align-items: center;
                padding: 3rem;
            }
        }

        /* ESTILOS PARA EL POST-IT (event-details-note) */
        .event-details-note {
            background-color: #fff89a;
            padding: 15px;
            border-radius: 0.4rem;
            
            box-shadow: 
                -2px -2px 0 0px rgba(255,255,255,0.7),
                2px 2px 0 0px rgba(0,0,0,0.2),
                5px 6px 18px rgba(0, 0, 0, 0.4),
                inset 0 0 10px rgba(0,0,0,0.05);

            transform: rotate(-3.5deg);
            font-family: 'Patrick Hand', cursive;
            color: #0a1c63;
            z-index: 40;
            max-width: 300px;
            height: 300px;
            overflow: hidden; 
            width: 100%;
            text-align: left;
            line-height: 1; 
            font-size: 0.95rem;
            white-space: pre-wrap;
            opacity: 1;
            position: relative;
            margin: 0;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: flex-start;
        }

        @media (min-width: 768px) {
            .event-details-note {
                /* transform: rotate(-5deg); */
            }
        }

        /* Ajustes para párrafos y títulos dentro del post-it */
        .event-details-note p {
            margin: 0;
            font-family: inherit;
            color: inherit;
            text-shadow: 0.5px 0.5px 1px rgba(0, 0, 0, 0.05);
            flex-shrink: 0;
            width: 100%;
        }
        .event-details-note p strong {
            font-weight: bold;
        }

        .event-details-note p:first-child {
            margin-top: 0;
        }
        .event-details-note p:last-child {
            margin-bottom: 0;
        }

        .event-details-note h3 {
            font-size: 1.1rem;
            font-weight: bold;
            margin-top: 0.4rem;
            margin-bottom: 0.2rem;
            color: #0a1c63;
            flex-shrink: 0;
            width: 100%;
        }
        .event-details-note h3:first-of-type {
            margin-top: 0;
        }
        /* Separador para las secciones */
        .event-details-note .section-divider {
            border-bottom: 1px dashed rgba(0, 0, 0, 0.2);
            margin: 0.4rem 0;
            width: 80%;
            height: 1px;
            display: block;
            transform: rotate(-0.5deg);
            flex-shrink: 0;
        }

        /* Estilo para la "firma" */
        .event-details-note .signature {
            font-family: 'Permanent Marker', cursive;
            font-size: 0.8rem;
            text-align: right;
            width: 100%;
            margin-top: auto;
            color: #0a1c63;
            transform: rotate(1deg);
        }


        /* ESTILOS PARA EL BOTÓN CTA (cta-button-scribble) */
        .cta-button-scribble {
            font-family: 'Permanent Marker', cursive;
            background-color: #ff4081;
            color: #fff;
            padding: 0.9rem 2.8rem;
            border: 3px dashed #fff;
            border-radius: 9999px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.35);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 2rem;
            transition: all 0.3s ease;
            display: block; /* Cambiamos a 'block' para que 'text-align' funcione */
            text-align: center; /* ¡Centrar el texto dentro del botón! */

            transform: rotate(-1deg);
            z-index: 20;
            position: relative;
            font-size: 1.3rem;
            animation: scribblePopIn 1s ease-out 1s both;
            text-decoration: none;
        }
        /* El contenedor padre ya tiene align-items: center para centrar el botón completo */
        .image-and-cta-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center; /* Esto centra el botón completo debajo de la Polaroid */
            gap: 2rem;
            width: 100%;
            max-width: 300px;
        }


        /* ESTILOS PARA EL RELIEVE Y DIMENSIONES POLAROID */
        .polaroid-image-placeholder {
            background-color: #f0f0f0;
            border: 1px solid rgba(0, 0, 0, 0.05);

            box-shadow:
                -2px -2px 5px rgba(255, 255, 255, 0.6),
                2px 2px 5px rgba(0, 0, 0, 0.3),
                0 8px 15px rgba(0, 0, 0, 0.4),
                inset 0 0 10px rgba(0, 0, 0, 0.2);

            padding: 20px 20px 70px 20px;
            
            text-align: center;
            font-family: 'Indie Flower', cursive;
            color: #333;
            transform: rotate(2deg);
            transition: transform 0.3s ease;
            width: 100%;
            max-width: 250px;
            
            height: auto;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            overflow: hidden;
        }

        .polaroid-image-placeholder img {
            width: 100%;
            height: auto;
            max-height: 100%;
            object-fit: cover;
            display: block;
            border: 1px solid rgba(0,0,0,0.1);
            margin: 0;
        }


        .cta-button-scribble::after {
            content: '';
            position: absolute;
            bottom: 0.5rem;
            left: 1rem;
            width: 80%;
            height: 4px;
            background: rgba(255, 255, 255, 0.8);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.4s ease-out;
            border-radius: 2px;
        }

        .cta-button-scribble:hover::after {
            transform: scaleX(1);
        }

        .cta-button-scribble:hover {
            background-color: #f50057;
            transform: scale(1.05) rotate(-2deg);
            box-shadow: 0 10px 25px rgba(0,0,0,0.4);
        }

        /* KEYFRAME ANIMATIONS (solo scribble) */
        @keyframes scribblePopIn {
            0% { opacity: 0; transform: scale(0.5) rotate(-20deg); }
            50% { opacity: 1; transform: scale(1.1) rotate(-5deg); }
            100% { opacity: 1; transform: scale(1) rotate(-1deg); }
        }

        /* MEDIA QUERIES PARA RESPONSIVIDAD */
        @media (max-width: 767px) {
            .event-details-note {
                position: relative;
                transform: rotate(0deg);
                margin-top: 1rem;
                margin-bottom: 2rem;
                font-size: 1.2rem;
                width: 95%;
                max-width: 400px;
                text-align: center;
                box-shadow: 2px 4px 12px rgba(0, 0, 0, 0.2);
            }
            .polaroid-image-placeholder {
                transform: rotate(0deg);
            }
            .cta-button-scribble {
                margin-top: 1.5rem;
            }
        }
    </style>
@endsection

@section('content')
    <div class="flyer-canvas">
        {{-- Aquí se coloca el post-it --}}
        <div class="event-details-note">
            {{-- Título principal del evento --}}
            <p>{{ $data['mainTitle'] ?? 'TRANSFORMA TU FUTURO' }}</p>
            @if(isset($data['subtitle']) && !empty($data['subtitle']))
                <p>{{ $data['subtitle'] ?? 'Invitación a Oportunidad de Negocio' }}</p>
            @endif
            {{-- La línea de presentadores se movió a la firma --}}

            <div class="section-divider"></div>

            <p><strong>{{ $data['event']['date'] ?? 'Viernes, 27 de Junio' }}</strong></p>
            <p><strong>{{ $data['event']['time'] ?? '8 PM (Hora Ecuador)' }}</strong></p>
            <p>{{ $data['event']['platform'] ?? 'Vía Zoom 9513664473' }}</p>
            <p>{{ $data['event']['platform_details'] ?? 'ID: Clave: SEN' }}</p>

            @if(isset($data['speaker']['name']) && !empty($data['speaker']['name']))
                <div class="section-divider"></div>
                {{-- No mostramos el título "Sobre el Orador:" --}}
                <p><strong>{{ $data['speaker']['name'] ?? 'Claudia Martínez' }}</strong></p>
                @if(isset($data['speaker']['title']) && !empty($data['speaker']['title']))
                    <p>{{ $data['speaker']['title'] ?? 'Oradora Especial' }}</p>
                @endif
                @if(isset($data['speaker']['quote']) && !empty($data['speaker']['quote']))
                    <p><em>"{{ $data['speaker']['quote'] ?? 'Una líder con la visión y experiencia para guiarte hacia el éxito' }}"</em></p>
                @endif
            @endif

            <p class="signature">{{ $data['presenters'] ?? 'SOCIAL ECONOMIC NETWORKERS' }}</p>
        </div>

        {{-- Contenedor para la imagen Polaroid y el botón CTA --}}
        <div class="image-and-cta-wrapper">
            {{-- TU IMAGEN TIPO POLAROID CON LA RUTA PROPORCIONADA --}}
            <div class="polaroid-image-placeholder">
                @if(isset($data['speaker']['image']))
                    <img src="{{ asset('storage/flyers/' . $data['speaker']['image']) }}"
                         alt="Foto de {{ $data['speaker']['name'] ?? 'el orador' }}">
                @else
                    {{-- Mensaje o placeholder si no hay imagen --}}
                    <span style="font-family: 'Permanent Marker', cursive; font-size: 1.5rem; text-align: center; display: block;">Carga tu<br>imagen aquí</span>
                @endif
            </div>

            {{-- El botón CTA ahora está junto a la imagen en el wrapper --}}
            <a href="{{ $data['cta']['link'] ?? '#' }}" class="cta-button-scribble">
                {{ $data['cta']['button_text'] ?? '✍️ ¡QUIERO PARTICIPAR!' }}
            </a>
        </div>
    </div>
@endsection