@extends('layouts.app')

@section('content')
<div class="container">
    <h1>📦 Generación de Seeders 4Life por País</h1>

    <hr>

    <h3>PASO 1 — Descargar el HTML</h3>

    <p>
        Ir a:
        <strong>https://{pais}.4life.com/pepeyclaudia/shop/all/(1-100)</strong>
    </p>

    <ul>
        <li>Click derecho → Ver código fuente</li>
        <li>Copiar TODO el HTML</li>
        <li>Guardar como:</li>
    </ul>

    <pre>
/var/www/html/pepemegia.com/storage/app/seeders_html/{pais}_all.html
    </pre>

    <hr>

    <h3>PASO 2 — Generar el Seeder</h3>

    <pre>
php artisan fl:make-products-seeder {pais}
    </pre>

    <p>
        Ejemplo:
    </p>

    <pre>
php artisan fl:make-products-seeder spain
    </pre>

    <hr>

    <h3>PASO 3 — Ejecutar el Seeder</h3>

    <pre>
php artisan db:seed --class=FlProducts{PaisCapitalizado}Seeder
    </pre>

    <p>
        Ejemplo:
    </p>

    <pre>
php artisan db:seed --class=FlProductsSpainSeeder
    </pre>

    <hr>

    <h3>🔎 Verificar Productos</h3>

    <pre>
php artisan tinker --execute="echo DB::table('fl_products')->where('country_id',ID)->count();"
    </pre>

    <hr>

    <p style="color:red;">
        ⚠️ Asegúrate que el país exista en la tabla <strong>fl_countries</strong>
    </p>
</div>
@endsection
