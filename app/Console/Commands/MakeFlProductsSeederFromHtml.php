<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Services\Fl\SeederFromHtmlGenerator;

class MakeFlProductsSeederFromHtml extends Command
{
    protected $signature = 'fl:make-products-seeder
        {country_code : Subdominio 4Life / fl_countries.code (ej: chile, spain, costarica)}
        {html_path? : (Opcional) Ruta al HTML. Si no se pasa: storage/app/seeders_html/{code}_all.html}
        {--output= : Ruta del seeder (default: database/seeders/FlProducts{Country}Seeder.php)}';

    protected $description = 'Genera un seeder de fl_products desde un HTML "shop/all" guardado localmente, usando fl_countries como config.';

    public function handle(SeederFromHtmlGenerator $generator): int
    {
        $code = strtolower(trim($this->argument('country_code')));

        // 1) Leer config desde BD
        $country = DB::table('fl_countries')
            ->select(['id', 'code', 'name', 'shop_url', 'currency_code', 'locale'])
            ->where('code', $code)
            ->first();

        if (!$country) {
            $this->error("No existe el país con code='{$code}' en fl_countries.");
            $this->line("Ejemplo: php artisan fl:make-products-seeder chile");
            return self::FAILURE;
        }

        // 2) Resolver HTML path (por defecto: storage/app/seeders_html/{code}_all.html)
        $htmlPathArg = $this->argument('html_path');
        $defaultHtmlPath = storage_path("app/seeders_html/{$code}_all.html");
        $htmlPath = $htmlPathArg ? base_path($htmlPathArg) : $defaultHtmlPath;

        if (!File::exists($htmlPath)) {
            $this->error("No existe el HTML: {$htmlPath}");
            $this->line("Ruta esperada por defecto: {$defaultHtmlPath}");
            return self::FAILURE;
        }

        // 3) Base URL desde shop_url (ej: https://chile.4life.com/corp/shop/all)
        $baseUrl = $this->baseUrlFromShopUrl($country->shop_url);
        if (!$baseUrl) {
            $this->error("No pude derivar base_url desde shop_url: {$country->shop_url}");
            return self::FAILURE;
        }

        // 4) Nombre del seeder / class
        $countryStudly = Str::studly($country->code); // spain -> Spain
        $defaultOutput = base_path("database/seeders/FlProducts{$countryStudly}Seeder.php");
        $outputPath = $this->option('output') ?: $defaultOutput;

        // 5) Generar
        try {
            $result = $generator->generate(
                countryName: $countryStudly,
                htmlPath: $htmlPath,
                countryId: (int) $country->id,
                currencyCode: strtoupper($country->currency_code),
                baseUrl: rtrim($baseUrl, '/'),
                outputPath: $outputPath
            );

            $this->info("Seeder generado: {$result['output']}");
            $this->line("País: {$country->name} ({$country->code}) | country_id={$country->id} | currency={$country->currency_code} | locale={$country->locale}");
            $this->line("Productos detectados en HTML: {$result['count']}");
            $this->line("HTML usado: {$htmlPath}");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Error: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    private function baseUrlFromShopUrl(?string $shopUrl): ?string
    {
        if (!$shopUrl) return null;
        $parts = parse_url($shopUrl);
        if (empty($parts['scheme']) || empty($parts['host'])) return null;
        return $parts['scheme'] . '://' . $parts['host'];
    }
}
