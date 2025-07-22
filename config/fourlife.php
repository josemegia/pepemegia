
<?php

// config/fourlife.php

return [
    'default' => [
        'codigo' => env('DEFAULT_CODIGO', '0000000'),
        'alias' => env('DEFAULT_ALIAS', 'default'),
        'nombre' => env('DEFAULT_NOMBRE', 'Nombre por defecto'),
        'email' => env('DEFAULT_EMAIL', 'email@example.com'),
        'telefono' => env('DEFAULT_TELEFONO', '+000 00000000'),
        'nombre_responsable' => env('DEFAULT_NOMBRE_RESPONSABLE', 'Responsable'),
        'NIF' => env('DEFAULT_NIF', 'X-0000000'),
        'direccion_responsable' => env('DEFAULT_DIRECCION_RESPONSABLE', 'Dirección Responsable'),
        'direccion' => env('DEFAULT_DIRECCION', 'Dirección'),
        'ciudad' => env('DEFAULT_CIUDAD', 'Ciudad'),
        'whatsapp' => env('DEFAULT_WHATSAPP', ''),
        'dominio' => config('app.url', 'PepeMegia.com'),
    ],
];