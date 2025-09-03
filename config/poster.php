<?php

return [
    'defaults' => [
        // Preview
        'preview_scale' => 0.45,        // 45%
        'fit_contain'   => true,        // ver completo (sin recorte) en pantalla

        // Texto anverso
        'size'  => 'text-3xl',          // "Grande"
        'align' => 'items-center text-center', // "Centro"

        // Posiciones (en % del A4)
        'anverso_x' => 50,
        'anverso_y' => 90,
        'qr_x'      => 27,
        'qr_y'      => 53,

        // Tamaño QR (px)
        'qr_size'   => 160,
    ],
];
