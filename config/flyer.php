<?php

return [

    // --- DATOS GENERALES DEL EVENTO ---
    'presenters' => 'SOCIAL ECONOMIC NETWORKERS',
    'mainTitle' => 'TRANSFORMA TU FUTURO',
    'subtitle' => 'Invitación a Oportunidad de Negocio',

    'event' => [
        'date' => 'Viernes, 27 de Junio',
        'time' => '8 PM (Hora Ecuador)',
        'platform' => 'Vía Zoom 9513664473',
        'platform_details' => 'Clave: SEN',
    ],

    'speaker' => [
        'name' => 'Claudia Martínez',
        'title' => 'Oradora Especial',
        'quote' => 'Una líder con la visión y experiencia para guiarte hacia el éxito',
        'image' => 'claudia.png',
    ],

    'cta' => [
        'button_text' => '¡QUIERO PARTICIPAR!',
        'link' => 'https://zoom.us/j/9513664473',
        'footer_text' => 'Un evento que puede cambiar tu perspectiva',
    ],
    
    // --- CONTROL DE EXPIRACIÓN Y LÍMITE ---
    'max_flyers_per_user' => 20,         // Máximo flyers por usuario
    'flyer_expiration_days' => 7,        // Días de vida de cada flyer compartido
    'device_selected' => 'iPhone 14 Pro Max',
    'browser_selected' => 'chromium',
    'devices' => [
        'iPhone 14 Pro Max',
        'iPhone SE',
        'Pixel 5',
        'Galaxy S9+',
        'Galaxy Note 10',
        'iPad Mini',
        'iPhone 12',
        'Pixel 2',
        'Nexus 6',
        'iPhone XR',
    ],

    'browsers' => [
        'chromium',
        'firefox',
        'webkit',
    ],

    // --- SISTEMA DE FORMATOS DISPONIBLES ---
    'default_format' => 'overlay',

    'formats' => [
        'standard' => [
            'name' => 'Estándar',
            'view' => 'flyer.formats.standard',
            'description' => 'Un diseño clásico y efectivo.',
        ],
        'modern' => [
            'name' => 'Moderno',
            'view' => 'flyer.formats.modern',
            'description' => 'Diseño profesional de dos columnas.',
        ],
        'minimalist' => [
            'name' => 'Minimalista',
            'view' => 'flyer.formats.minimalist',
            'description' => 'Centrado en el mensaje principal.',
        ],
        'event_centric' => [
            'name' => 'Enfocado en Evento',
            'view' => 'flyer.formats.event_centric',
            'description' => 'Destaca los detalles del evento.',
        ],
        '2025' => [
            'name' => 'Diseño 2025',
            'view' => 'flyer.formats.2025',
            'description' => 'Algo Nuevo.',
        ],
        'overlay' => [
            'name' => 'Dynamic Overlay',
            'view' => 'flyer.formats.overlay',
            'description' => 'Un diseño inmersivo y moderno con imagen de fondo y superposiciones.',
        ],
    ],

    // --- TEMAS VISUALES DISPONIBLES ---
    'themes' => [

        'default' => [
            'font_family_class' => 'font-sans',
            'font_link' => 'https://fonts.googleapis.com/css2?family=Inter&display=swap',
            'classes' => [
                'gradient'          => 'from-indigo-900 via-purple-800 to-pink-700',
                'gradient_start_bg' => 'bg-blue-900',
                'highlight_text'    => 'text-pink-300',
                'highlight_bg'      => 'bg-yellow-400',
                'highlight_border'  => 'border-pink-500',
                'cta_button'        => 'bg-pink-600 hover:bg-pink-500 text-white',
            ],
        ],

        // Temas extendidos
        'professional' => [
            'font_family_class' => 'font-[Poppins]',
            'font_link' => 'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;900&display=swap',
            'classes' => [
                'gradient'           => 'from-blue-900 via-purple-700 to-pink-400',
                'highlight_text'     => 'text-pink-400',
                'highlight_border'   => 'border-pink-400',
                'highlight_bg'       => 'bg-pink-400',
                'gradient_start_bg'  => 'bg-blue-900',
                'cta_button'         => 'bg-white text-blue-900 hover:bg-pink-100',
            ],
        ],

        'neon' => [
            'font_family_class' => 'font-[Exo_2]',
            'font_link' => 'https://fonts.googleapis.com/css2?family=Exo+2:wght@400;700;900&display=swap',
            'classes' => [
                'gradient'           => 'from-gray-900 via-black to-gray-900',
                'highlight_text'     => 'text-cyan-400',
                'highlight_border'   => 'border-cyan-400',
                'highlight_bg'       => 'bg-cyan-400',
                'gradient_start_bg'  => 'bg-gray-900',
                'cta_button'         => 'bg-fuchsia-500 text-white hover:bg-fuchsia-600',
            ],
        ],

        'vibrant' => [
            'font_family_class' => 'font-[Montserrat]',
            'font_link' => 'https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700;900&display=swap',
            'classes' => [
                'gradient'           => 'from-orange-600 via-amber-400 to-orange-100',
                'highlight_text'     => 'text-orange-500',
                'highlight_border'   => 'border-orange-500',
                'highlight_bg'       => 'bg-orange-600',
                'gradient_start_bg'  => 'bg-orange-600',
                'cta_button'         => 'bg-gray-900 text-white hover:bg-gray-700',
            ],
        ],

        'corporate' => [
            'font_family_class' => 'font-[Lato]',
            'font_link' => 'https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700&display=swap',
            'classes' => [
                'gradient'           => 'from-blue-800 via-blue-600 to-blue-400',
                'highlight_text'     => 'text-blue-200',
                'highlight_border'   => 'border-blue-200',
                'highlight_bg'       => 'bg-blue-200',
                'gradient_start_bg'  => 'bg-blue-800',
                'cta_button'         => 'bg-white text-blue-800 hover:bg-blue-100',
            ],
        ],

        'elegant' => [
            'font_family_class' => 'font-[Playfair_Display]',
            'font_link' => 'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&display=swap',
            'classes' => [
                'gradient'           => 'from-gray-900 via-gray-800 to-gray-900',
                'highlight_text'     => 'text-yellow-400',
                'highlight_border'   => 'border-yellow-400',
                'highlight_bg'       => 'bg-yellow-400',
                'gradient_start_bg'  => 'bg-gray-900',
                'cta_button'         => 'bg-yellow-400 text-gray-900 hover:bg-yellow-500',
            ],
        ],

        'natural' => [
            'font_family_class' => 'font-[Nunito]',
            'font_link' => 'https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;800&display=swap',
            'classes' => [
                'gradient'           => 'from-green-800 via-green-600 to-green-300',
                'highlight_text'     => 'text-yellow-600',
                'highlight_border'   => 'border-yellow-600',
                'highlight_bg'       => 'bg-yellow-600',
                'gradient_start_bg'  => 'bg-green-800',
                'cta_button'         => 'bg-white text-green-800 hover:bg-gray-100',
            ],
        ],
    ],

];
