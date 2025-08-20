<?php

return [
    
    // Archivo de menú general
    'file_check' => '/menu.php',

    // Idioma por defecto del menú
    'default' => 'es',

    'divisas' => [
        'us' => [
            'idioma'  => 'en_US',
            'cambio'  => [1, 1],
            'dec'     => true,
            'code'    => 'USD',
            'symbol'  => '$',
        ],
        
        'co' => [
            'idioma'  => 'es_CO',
            'cambio'  => [2400, 2400],
            'dec'     => false,
            'code'    => 'COP',
            'symbol'  => '$',
        ],
        
        'cr' => [
            'idioma'  => 'es_CR',
            'cambio'  => [580, 580],
            'dec'     => false,
            'code'    => 'CRC',
            'symbol'  => '₡',
        ],
        
        'es' => [
            'idioma'  => 'es_ES',
            'cambio'  => [0.8, 0.769],
            'dec'     => true,
            'code'    => 'EUR',
            'symbol'  => '€',
        ],
        
        'mx' => [
            'idioma'  => 'es_MX',
            'cambio'  => [18, 18],
            'dec'     => false,
            'code'    => 'MXN',
            'symbol'  => '$',
        ],
    ],
    
    // Ítems del menú principal
    'items' => [

        // --- Selector de Idioma (Tipo Especial) ---
        [
            'type'    => 'locale_selector',
            'lang'    => 'es',
            'label'   => 'menu.lang',
        ],

        // --- Panel de Administrador (Carga dinámica) ---
        [
            'label'   => 'menu.admin_panel',
            'can'     => 'view-admin-dashboard',
            'title'   => 'menu.admin_panel_title',
            'content' => [
                ['type' => 'icon', 'value' => 'fas fa-user-shield'],
                ['type' => 'text', 'key' => 'menu.admin_panel'],
            ],
            'submenu' => config('admin_menu.items', []),
        ],

        [
            'label'   => 'menu.login_label',
            'route'   => 'login',
            'auth'    => 'guest',
            'content' => [
                ['type' => 'icon', 'value' => 'fas fa-sign-in-alt'],
                ['type' => 'text', 'key' => 'menu.login_text'],
            ],
            'title'   => 'menu.login_title',
        ],

        // --- SEN (Planes y Flyers) ---
        [
            'label'   => 'menu.sen_label',
            'content' => [
                ['type' => 'icon', 'value' => 'fas fa-project-diagram'],
                ['type' => 'text', 'key' => 'menu.sen_text'],
            ],
            'title'   => 'menu.sen_title',
            'submenu' => [
                [
                    'route'  => 'plan.index',
                    'target' => '_blank',
                    'title'  => 'menu.sen_plan_title',
                    'content' => [
                        //['type' => 'icon', 'value' => 'fas fa-caret-right'],
                        //['type' => 'text', 'value' => ' '],
                        ['type' => 'icon', 'value' => 'fas fa-file-alt'],
                        ['type' => 'text', 'key' => 'menu.sen_plan_text'],
                    ],
                ],
                [
                    'route'  => 'flyer.show',
                    'target' => '_blank',
                    'title'  => 'menu.sen_flyers_title',
                    'content' => [
                        //['type' => 'icon', 'value' => 'fas fa-caret-right'],
                        //['type' => 'text', 'value' => ' '],
                        ['type' => 'icon', 'value' => 'fas fa-bullhorn'],
                        ['type' => 'text', 'key' => 'menu.sen_flyers_text'],
                    ],
                ],
            ],
        ],

        // --- Servicios ---
        [
            'label'   => 'menu.services_label',
            'title'   => 'menu.services_title',
            'content' => [
                ['type' => 'icon', 'value' => 'fas fa-briefcase'],
                ['type' => 'text', 'key' => 'menu.services_text'],
            ],
        ],

        // --- Varios ---
        [
            'label'   => 'menu.datos',
            'content' => [
                ['type' => 'icon', 'value' => 'fas fa-grip-lines'],
                ['type' => 'text', 'key' => 'menu.datos'],
            ],
            'title'   => 'menu.datos',
            'submenu' => [

                // --- Contacto vía WhatsApp (multilenguaje dinámico) ---
                [
                    'label'   => 'menu.contact_label',
                    'fn' => [
                        'type' => 'whatsapp',
                        'template' => 'menu.contact_message_template',
                    ],
                    'target'  => '_blank',
                    'title'   => 'menu.contact_title',
                    'content' => [
                        ['type' => 'icon', 'value' => 'fab fa-whatsapp'],
                        ['type' => 'text', 'key' => 'menu.contact_text'],
                    ],
                ],

                // --- Legal ---
                [
                    'label'   => 'menu.legal_label',
                    'route'   => 'privacidad',
                    'title'   => 'menu.legal_title',
                    'content' => [
                        ['type' => 'icon', 'value' => 'fas fa-handshake'],
                        ['type' => 'text', 'key' => 'menu.legal_text'],
                    ],
                ],
            ],
        ],

        // --- Inicio (puedes descomentar si lo quieres visible) ---
        /*
        [
            'label'   => 'menu.home_label',
            'route'   => 'inicio',
            'content' => [
                ['type' => 'icon', 'value' => 'fas fa-home'],
                ['type' => 'text', 'key' => 'menu.home_text'],
            ],
            'title'   => 'menu.home_title',
        ],
        */
    ],

];
