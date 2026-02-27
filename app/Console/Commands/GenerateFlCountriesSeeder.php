<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class GenerateFlCountriesSeeder extends Command
{
    protected $signature = 'fl:generate-countries-seeder
        {--map=/etc/nginx/conf.d/geoip2.map : Ruta del geoip2.map}
        {--output=database/seeders/FlCountriesSeeder.php : Archivo seeder a generar}';

    protected $description = 'Genera FlCountriesSeeder.php desde geoip2.map filtrando Europa + hispanohablantes (y US español).';

    public function handle(): int
    {
        $mapPath = $this->option('map');
        $output  = base_path($this->option('output'));

        if (!is_readable($mapPath)) {
            $this->error("No puedo leer el map: {$mapPath}");
            return self::FAILURE;
        }

        // Europa (lista amplia práctica) + Hispanohablantes + US español
        $europe = [
            'AD','AT','BE','BG','BA','BY','CH','CY','CZ','DE','DK','EE','ES','FI','FR','GB','GR','HR','HU',
            'IE','IT','LT','LU','LV','MK','MT','NL','NO','PL','PT','RO','RS','RU','SE','SI','SK','SM','TR','UA'
        ];

        $spanishSpeaking = [
            'AR','BO','CL','CO','CR','DO','EC','SV','GQ','GT','HN','MX','NI','PA','PE','PY','VE','US'
        ];

        $allowedIso = array_fill_keys(array_merge($europe, $spanishSpeaking), true);

        $rows = [];
        $lines = file($mapPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, 'default')) {
                continue;
            }

            // Formato: "ES spain;"
            if (!preg_match('/^([A-Z]{2})\s+([a-z0-9-]+);$/', $line, $m)) {
                continue;
            }

            $iso  = $m[1];
            $slug = $m[2];

            if (!isset($allowedIso[$iso])) {
                continue;
            }

            $name = $this->guessCountryName($iso, $slug);

            [$currencyCode, $currencySymbol] = $this->guessCurrency($iso);

            $rows[] = [
                'code' => $slug,
                'name' => $name,
                'iso_code' => $iso,
                'shop_url' => "https://{$slug}.4life.com/corp/shop/all",
                'currency_code' => $currencyCode,
                'currency_symbol' => $currencySymbol,
                'locale' => $this->guessLocale($iso),
                'is_active' => true,
                'scrape_method' => 'puppeteer',
                'scrape_status' => 'never',
                // Guardamos como JSON string para DB
                'scrape_days' => json_encode([1, 21]),
            ];
        }

        usort($rows, fn($a, $b) => strcmp($a['iso_code'], $b['iso_code']));

        // Solo columnas que existen en fl_countries
        $table = 'fl_countries';
        if (!Schema::hasTable($table)) {
            $this->error("No existe la tabla {$table}. ¿Corriste migraciones?");
            return self::FAILURE;
        }

        $cols = array_flip(Schema::getColumnListing($table));
        $rows = array_map(fn($row) => array_intersect_key($row, $cols), $rows);

        file_put_contents($output, $this->renderSeeder($rows));

        $this->info("Seeder generado: {$output}");
        $this->info("Países incluidos: " . count($rows));

        return self::SUCCESS;
    }

    private function guessLocale(string $iso): string
    {
        return match ($iso) {
            'GB', 'IE' => 'en_GB',
            'US' => 'es_US',
            default => 'es_ES',
        };
    }

    private function guessCurrency(string $iso): array
    {
        // Eurozona (aprox. para los que vamos a incluir)
        $euro = ['AD','AT','BE','CY','DE','EE','ES','FI','FR','GR','IE','IT','LT','LU','LV','MT','NL','PT','SI','SK','SM'];

        if (in_array($iso, $euro, true)) {
            return ['EUR', '€'];
        }

        // Europa no-euro (y otros incluidos)
        $map = [
            'GB' => ['GBP', '£'],
            'CH' => ['CHF', 'CHF'],
            'DK' => ['DKK', 'kr'],
            'NO' => ['NOK', 'kr'],
            'SE' => ['SEK', 'kr'],
            'PL' => ['PLN', 'zł'],
            'CZ' => ['CZK', 'Kč'],
            'HU' => ['HUF', 'Ft'],
            'RO' => ['RON', 'lei'],
            'BG' => ['BGN', 'лв'],
            'HR' => ['EUR', '€'], // Croacia usa EUR actualmente
            'TR' => ['TRY', '₺'],
            'UA' => ['UAH', '₴'],
            'RU' => ['RUB', '₽'],
            'BY' => ['BYN', 'Br'],
            'RS' => ['RSD', 'дин'],
            'MK' => ['MKD', 'ден'],
            'BA' => ['BAM', 'KM'],

            // Hispanohablantes
            'US' => ['USD', '$'],
            'MX' => ['MXN', '$'],
            'CO' => ['COP', '$'],
            'CL' => ['CLP', '$'],
            'PE' => ['PEN', 'S/'],
            'AR' => ['ARS', '$'],
            'BO' => ['BOB', 'Bs'],
            'CR' => ['CRC', '₡'],
            'DO' => ['DOP', '$'],
            'EC' => ['USD', '$'],
            'PA' => ['USD', '$'],
            'GT' => ['GTQ', 'Q'],
            'HN' => ['HNL', 'L'],
            'NI' => ['NIO', 'C$'],
            'SV' => ['USD', '$'],
            'PY' => ['PYG', '₲'],
            'VE' => ['VES', 'Bs.'],
            'GQ' => ['XAF', 'FCFA'],
        ];

        return $map[$iso] ?? ['EUR', '€']; // fallback seguro (no NULL)
    }

    private function guessCountryName(string $iso, string $slug): string
    {
        $map = [
            'US' => 'United States (Spanish Store)',
            'GB' => 'United Kingdom',
            'CZ' => 'Czech Republic',
            'DO' => 'Dominican Republic',
            'GQ' => 'Equatorial Guinea',
            'MK' => 'North Macedonia',
            'RS' => 'Serbia',
            'BA' => 'Bosnia and Herzegovina',
        ];

        if (isset($map[$iso])) {
            return $map[$iso];
        }

        try {
            if (class_exists(\Symfony\Component\Intl\Countries::class)) {
                $n = \Symfony\Component\Intl\Countries::getName($iso);
                if ($n) return $n;
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return ucwords(str_replace(['-', '_'], ' ', $slug));
    }

    private function renderSeeder(array $rows): string
    {
        $exportRows = var_export($rows, true);

        return <<<PHP
<?php

namespace Database\\Seeders;

use Illuminate\\Database\\Seeder;
use App\\Models\\FlCountry;

class FlCountriesSeeder extends Seeder
{
    public function run(): void
    {
        \$countries = {$exportRows};

        foreach (\$countries as \$c) {
            FlCountry::updateOrCreate(
                ['code' => \$c['code']],
                \$c
            );
        }
    }
}

PHP;
    }
}
