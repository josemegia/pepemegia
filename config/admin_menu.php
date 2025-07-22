<?php
// config/admin_menu.php

return [
    'items' => [
        [
            'label'   => 'menu.dashboard',
            'route'   => 'admin.dashboard',
            'can'     => 'view-admin-dashboard',
            'content' => [
                ['type' => 'icon', 'value' => 'fas fa-chart-line'],
                ['type' => 'text', 'key' => 'menu.dashboard'],
            ],
            'title' => 'menu.dashboard_title',
        ],
        [
            'label'   => 'menu.user_management',
            'route'   => 'admin.users.index',
            'can'     => 'manage-users',
            'content' => [
                ['type' => 'icon', 'value' => 'fas fa-users'],
                ['type' => 'text', 'key' => 'menu.user_management'],
            ],
            'title' => 'menu.user_management_title',
        ],
        [
            'label'   => 'menu.recaptcha_management',
            'route'   => 'admin.recaptcha.index',
            'can'     => 'manage-recaptcha',
            'content' => [
                ['type' => 'icon', 'value' => 'fas fa-shield-alt'],
                ['type' => 'text', 'key' => 'menu.recaptcha_management'],
            ],
            'title' => 'menu.recaptcha_management_title',
        ],
        [
            'label'   => 'menu.airport_references',
            'route'   => 'admin.airports.tool',
            'can'     => 'manage-airports',
            'content' => [
                ['type' => 'icon', 'value' => 'fas fa-plane-departure'],
                ['type' => 'text', 'key' => 'menu.airport_references'],
            ],
            'title' => 'menu.airport_references_title',
        ],
        [
            'label'   => 'menu.stays_by_country',
            'route'   => 'admin.stays.index',
            'can'     => 'view-stays-report',
            'content' => [
                ['type' => 'icon', 'value' => 'fas fa-globe-americas'],
                ['type' => 'text', 'key' => 'menu.stays_by_country'],
            ],
            'title' => 'menu.stays_by_country_title',
        ],
        [
            'label'   => 'menu.horizon',
            'url'     => '/horizon',
            'target'  => '_blank',
            'can'     => 'access-horizon',
            'content' => [
                ['type' => 'icon', 'value' => 'fas fa-window-restore'],
                ['type' => 'text', 'key' => 'menu.horizon'],
            ],
            'title' => 'menu.horizon_title',
        ],
        [
            'label'   => 'menu.settings',
            'can'     => 'view-settings',
            'content' => [
                ['type' => 'icon', 'value' => 'fas fa-cogs'],
                ['type' => 'text', 'key' => 'menu.settings'],
            ],
            'title' => 'menu.settings_title',
            'submenu' => [
                [
                    'label'   => 'menu.general_settings',
                    'route'   => 'admin.settings.general',
                    'can'     => 'edit-general-settings',
                    'content' => [
                        ['type' => 'icon', 'value' => 'fas fa-sliders-h'],
                        ['type' => 'text', 'key' => 'menu.general_settings'],
                    ],
                    'title' => 'menu.general_settings_title',
                ],
                [
                    'label'   => 'menu.email_settings',
                    'route'   => 'admin.settings.email',
                    'can'     => 'edit-email-settings',
                    'content' => [
                        ['type' => 'icon', 'value' => 'fas fa-envelope-open-text'],
                        ['type' => 'text', 'key' => 'menu.email_settings'],
                    ],
                    'title' => 'menu.email_settings_title',
                ],
                [
                    'label'   => 'menu.integrations',
                    'route'   => 'admin.settings.integrations',
                    'can'     => 'manage-integrations',
                    'content' => [
                        ['type' => 'icon', 'value' => 'fas fa-puzzle-piece'],
                        ['type' => 'text', 'key' => 'menu.integrations'],
                    ],
                    'title' => 'menu.integrations_title',
                ],
            ],
        ],
        [
            'label'   => 'menu.tools',
            'can'     => 'view-tools',
            'content' => [
                ['type' => 'icon', 'value' => 'fas fa-tools'],
                ['type' => 'text', 'key' => 'menu.tools'],
            ],
            'title' => 'menu.tools_title',
            'submenu' => [
                [
                    'label'   => 'menu.phpmyadmin',
                    'url'     => 'https://phpmyadmin.pepeyclaudia.com',
                    'target'  => '_blank',
                    'can'     => 'access-phpmyadmin',
                    'content' => [
                        ['type' => 'icon', 'value' => 'fas fa-database'],
                        ['type' => 'text', 'key' => 'menu.phpmyadmin'],
                    ],
                    'title' => 'menu.phpmyadmin_title',
                ],
            ],
        ],
    ],
];
