<?php

namespace App\Services\Fl;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class SeederFromHtmlGenerator
{
    /**
     * Mapa simple para tratar de asignar reference_id por nombre local.
     * Ajusta/crece este mapa según tus países y naming local.
     */
    private array $referenceMap = [
        // reference_id => [aliases]
        1  => ['4life plus', '4life plus '],
        2  => ['ecm', 'transfer factor tri-factor', 'tri factor'],
        4  => ['tf - boost', 'tf-boost', 'tf boost'],
        6  => ['riovida stix'],
        8  => ['pro-tf', 'pro-tf chocolate', 'pro tf chocolate'],
        10 => ['pre/o biotics', 'preo biotics', 'pre o biotics'],
        25 => ['4life collagen', 'collagen'],
        47 => ['energy go stix', 'energy go stix - berry'],
        75 => ['riovida liquido', 'riovida líquido', 'transfer factor riovida'],
    ];

    private array $categoryHints = [
        'immune' => ['immune', 'sistema inmunitario', 'transfer factor', 'tf '],
        'digestive' => ['digest', 'digestiva', 'biotics', 'pre/o', 'intestinal'],
        'energy' => ['energ', 'energy'],
        'skin' => ['collagen', 'colágeno', 'piel', 'belleza', 'esencia joven'],
        'weight' => ['peso', 'weight', 'transform'],
        'cardio' => ['cardio', 'heart', 'corazón'],
        'general' => [],
    ];

    private array $formatHints = [
        'capsules' => ['capsule', 'cápsula', 'capsulas', 'cápsulas'],
        'tablets'  => ['tablet', 'tableta', 'tabletas'],
        'liquid'   => ['liquid', 'líquido', 'liquido'],
        'powder'   => ['powder', 'polvo', 'proteína', 'proteina'],
        'sachets'  => ['sachet', 'stix', 'sticks'],
        'softgels' => ['softgel'],
        'spray'    => ['spray'],
        'cream'    => ['cream', 'crema'],
        'chewables'=> ['chew', 'masticable'],
        'other'    => [],
    ];

    public function generate(
        string $countryName,
        string $htmlPath,
        int $countryId,
        string $currencyCode,
        string $baseUrl,
        string $outputPath
    ): array {
        if (!File::exists($htmlPath)) {
            throw new \RuntimeException("No existe el HTML: {$htmlPath}");
        }

        $html = File::get($htmlPath);

        [$products, $baseUrlDetected] = $this->parseProducts($html, $baseUrl, $countryId, $currencyCode);

        // Generar PHP del seeder
        $php = $this->buildSeederPhp(
            countryName: $countryName,
            countryId: $countryId,
            currencyCode: $currencyCode,
            products: $products
        );

        File::ensureDirectoryExists(dirname($outputPath));
        File::put($outputPath, $php);

        return [
            'output' => $outputPath,
            'count'  => count($products),
            'base_url' => $baseUrlDetected,
        ];
    }

    private function parseProducts(string $html, string $baseUrl, int $countryId, string $currencyCode): array
    {
        libxml_use_internal_errors(true);

        $dom = new DOMDocument();
        $dom->loadHTML($html);

        $xpath = new DOMXPath($dom);

        // Cada producto está en div.card[data-sku]
        $cards = $xpath->query("//div[contains(@class,'card') and @data-sku]");

        $products = [];

        foreach ($cards as $idx => $card) {
            $sku = $card->attributes->getNamedItem('data-sku')?->nodeValue;

            // URL del producto
            $a = $xpath->query(".//a[contains(@class,'card-link')]", $card)->item(0);
            $href = $a?->attributes?->getNamedItem('href')?->nodeValue;
            $url = $href ? $this->absoluteUrl($baseUrl, $href) : null;

            // Nombre (div.product-name > div)
            $nameNode = $xpath->query(".//div[contains(@class,'product-name')]//div[1]", $card)->item(0);
            $name = $nameNode ? trim(html_entity_decode($nameNode->textContent)) : null;

            // Imagen
            $img = $xpath->query(".//img[contains(@class,'product-img')]", $card)->item(0);
            $imgSrc = $img?->attributes?->getNamedItem('src')?->nodeValue
                ?: $img?->attributes?->getNamedItem('data-original')?->nodeValue;
            $imageUrl = $imgSrc ? html_entity_decode($imgSrc) : null;

            // Descripción corta (tagline p)
            $descNode = $xpath->query(".//div[contains(@class,'tagline')]//p", $card)->item(0);
            $description = $descNode ? trim(html_entity_decode($descNode->textContent)) : null;

            // Precio (span.green)
            $priceNode = $xpath->query(".//span[contains(@class,'green')]", $card)->item(0);
            $priceText = $priceNode ? trim($priceNode->textContent) : null;
            $price = $this->parseMoneyToInt($priceText);

            // Promo: si existe .flash-sale-flag o precio tachado
            $hasFlash = $xpath->query(".//span[contains(@class,'flash-sale-flag')]", $card)->length > 0;
            $hasStrike = $xpath->query(".//span[contains(@class,'strikethrough')]", $card)->length > 0;

            $promoDetails = null;
            if ($hasFlash) $promoDetails = 'Flash Sale';

            // Disponibilidad: si tiene clase gray-bg o out-of-stock
            $isAvailable = true;
            $classAttr = $card->attributes->getNamedItem('class')?->nodeValue ?? '';
            if (str_contains($classAttr, 'gray-bg')) {
                $isAvailable = false; // el HTML dice fuera de existencia
            }

            // is_pack: el HTML trae hidden input size="Paquete" vs "Unidad"
            $sizeNode = $xpath->query(".//input[@name='size']", $card)->item(0);
            $size = $sizeNode?->attributes?->getNamedItem('value')?->nodeValue;
            $isPack = is_string($size) && mb_strtolower(trim($size)) === 'paquete';

            // category y format por heurística (no viene perfecto del listado)
            $category = $this->guessCategory($name, $description);
            $format = $this->guessFormat($name, $description);

            $referenceId = $this->guessReferenceId($name);

            $products[] = [
                'country_id'          => $countryId,
                'reference_id'        => $referenceId,
                'external_id'         => $sku,
                'name'                => $name,
                'slug'                => Str::slug($name ?? ''),
                'url'                 => $url,
                'image_url'           => $imageUrl,
                'description'         => $description ?: ($name ?? ''),
                'price'               => $price,
                'price_wholesale'     => null,
                'price_loyalty'       => null,
                'currency_code'       => $currencyCode,
                'format'              => $format,
                'units_per_container' => null,
                'serving_size'        => null,
                'is_pack'             => $isPack,
                'is_available'        => $isAvailable,
                'is_on_promotion'     => ($hasFlash || $hasStrike),
                'promotion_details'   => $promoDetails,
                'category'            => $category,
                'sort_order'          => $idx + 1,
            ];
        }

        return [$products, $baseUrl];
    }

    private function buildSeederPhp(string $countryName, int $countryId, string $currencyCode, array $products): string
    {
        $class = "FlProducts{$countryName}Seeder";

        $rowsPhp = $this->exportArrayPhp($products);

        return <<<PHP
<?php

namespace Database\\Seeders;

use Illuminate\\Database\\Seeder;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Str;

class {$class} extends Seeder
{
    public function run(): void
    {
        \$rows = {$rowsPhp};

        \$now = now();
        \$rows = array_map(function (\$row) use (\$now) {
            \$row['created_at'] = \$row['created_at'] ?? \$now;
            \$row['updated_at'] = \$now;
            return \$row;
        }, \$rows);

        DB::table('fl_products')->upsert(
            \$rows,
            ['country_id', 'external_id'],
            [
                'reference_id',
                'name',
                'slug',
                'url',
                'image_url',
                'description',
                'price',
                'price_wholesale',
                'price_loyalty',
                'currency_code',
                'format',
                'units_per_container',
                'serving_size',
                'is_pack',
                'is_available',
                'is_on_promotion',
                'promotion_details',
                'category',
                'sort_order',
                'updated_at',
            ]
        );
    }
}

PHP;
    }

    private function exportArrayPhp(array $data): string
    {
        // Export bonito y estable (sin var_export horroroso)
        $lines = [];
        $lines[] = "[";
        foreach ($data as $row) {
            $lines[] = "            [";
            foreach ($row as $k => $v) {
                $lines[] = "                " . $this->phpKey($k) . " => " . $this->phpValue($v) . ",";
            }
            $lines[] = "            ],";
        }
        $lines[] = "        ]";
        return implode("\n", $lines);
    }

    private function phpKey(string $k): string
    {
        return "'" . addslashes($k) . "'";
    }

    private function phpValue($v): string
    {
        if ($v === null) return 'null';
        if (is_bool($v)) return $v ? 'true' : 'false';
        if (is_int($v) || is_float($v)) return (string)$v;
        return "'" . addslashes((string)$v) . "'";
    }

    private function parseMoneyToInt(?string $text): ?int
    {
        if (!$text) return null;
        // Ej: "$23.020" -> 23020
        $digits = preg_replace('/[^0-9]/', '', $text);
        return $digits === '' ? null : (int)$digits;
    }

    private function absoluteUrl(string $baseUrl, string $href): string
    {
        if (str_starts_with($href, 'http://') || str_starts_with($href, 'https://')) {
            return $href;
        }
        return rtrim($baseUrl, '/') . '/' . ltrim($href, '/');
    }

    private function guessReferenceId(?string $name): ?int
    {
        if (!$name) return null;
        $n = mb_strtolower(trim($name));

        foreach ($this->referenceMap as $refId => $aliases) {
            foreach ($aliases as $alias) {
                if (str_contains($n, mb_strtolower($alias))) {
                    return (int)$refId;
                }
            }
        }
        return null;
    }

    private function guessCategory(?string $name, ?string $desc): string
    {
        $haystack = mb_strtolower(trim(($name ?? '') . ' ' . ($desc ?? '')));
        foreach ($this->categoryHints as $cat => $keys) {
            foreach ($keys as $k) {
                if ($k !== '' && str_contains($haystack, mb_strtolower($k))) {
                    return $cat;
                }
            }
        }
        return 'general';
    }

    private function guessFormat(?string $name, ?string $desc): string
    {
        $haystack = mb_strtolower(trim(($name ?? '') . ' ' . ($desc ?? '')));
        foreach ($this->formatHints as $fmt => $keys) {
            foreach ($keys as $k) {
                if ($k !== '' && str_contains($haystack, mb_strtolower($k))) {
                    return $fmt;
                }
            }
        }
        return 'other';
    }
}
