<?php

// config/auth_menu.php

return [
    'items' => [
        [
            'label'   => 'Mi Bóveda',
            'url'     => '/admin/iframe?url=https://vault.pepemegia.com',
            'title'   => 'Acceder a mi bóveda de contraseñas',
            'content' => [
                ['type' => 'icon', 'value' => 'fas fa-key'],
                ['type' => 'text', 'value' => 'Mi Bóveda'],
            ],
        ],
        /*
        [
            'label'   => 'Seeders 4Life',
            'url'     => '/admin/docs/seeders',
            'title'   => 'Guía para generar seeders por país',
            'content' => [
                ['type' => 'icon', 'value' => 'fas fa-database'],
                ['type' => 'text', 'value' => 'Seeders 4Life'],
            ],
        ],
        */
        // --- Menú de Perfil de Usuario (para cualquier usuario autenticado) ---
        [
            'label'   => 'menu.profile_label',
            'title'   => 'menu.profile_title',
            'content' => [
                ['type' => 'icon', 'value' => 'fas fa-user-circle'],
                // Tipo especial para que la vista imprima el nombre del usuario.
                ['type' => 'dynamic_text', 'value' => 'user.name'],
            ],
            'submenu' => [
                [
                    'route'   => 'profile.dashboard', 
                    'content' => [
                        ['type' => 'icon', 'value' => 'fas fa-cog'],
                        ['type' => 'text', 'key' => 'menu.profile_text'],
                    ],
                ],
                [
                    'label'   => 'menu.logout_label',
                    'route'   => 'profile.logout',
                    'title'   => 'menu.logout_title',
                    'content' => [
                        ['type' => 'icon', 'value' => 'fas fa-sign-in-alt'],
                        ['type' => 'text', 'key' => 'menu.logout_text'],
                    ],
                ],
            ],
        ],
    ],
];
