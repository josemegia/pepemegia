<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\FlCountry;
use DOMDocument;
use DOMXPath;

class FlAdminProductsController extends Controller
{
    public function index()
    {
        $countries = FlCountry::where('is_active', true)->withCount('products')->orderBy('name')->get();
        return view('admin.fl-products.index', compact('countries'));
    }

    public function import(Request $request)
    {
        $request->validate(['html' => 'required|string|min:500']);
        $html = $request->input('html');
        $countryCode = $this->detectCountryFromHtml($html);

        if (!$countryCode) {
            return back()->with('error', 'No se pudo detectar el país del HTML. Pega el HTML completo de {pais}.4life.com/.../shop/all.');
        }

        $country = FlCountry::where('code', $countryCode)->first();
        if (!$country) {
            return back()->with('error', "País '{$countryCode}' no existe en fl_countries.");
        }

        $baseUrl = parse_url($country->shop_url, PHP_URL_SCHEME) . '://' . parse_url($country->shop_url, PHP_URL_HOST);
        $products = $this->parseProducts($html, $baseUrl, $country->id, strtoupper($country->currency_code));

        if (empty($products)) {
            return back()->with('error', 'No se encontraron productos en el HTML.');
        }

        $now = now();
        $rows = array_map(fn($r) => array_merge($r, ['created_at' => $now, 'updated_at' => $now]), $products);

        DB::table('fl_products')->upsert($rows, ['country_id', 'external_id'], [
            'reference_id','name','slug','url','image_url','description','price','price_wholesale',
            'price_loyalty','currency_code','format','units_per_container','serving_size','is_pack',
            'is_available','is_on_promotion','promotion_details','category','sort_order','updated_at',
        ]);

        return back()->with('success', "✅ {$country->name}: " . count($products) . " productos importados/actualizados.");
    }

    public function destroy(FlCountry $country)
    {
        $deleted = DB::table('fl_products')->where('country_id', $country->id)->delete();
        return back()->with('success', "🗑️ {$country->name}: {$deleted} productos eliminados.");
    }

    private function detectCountryFromHtml(string $html): ?string
    {
        if (preg_match_all('/https?:\/\/([a-z]+)\.4life\.com/i', $html, $m)) {
            $skip = ['www','media','media2','static','cdn','api','cmp'];
            $candidates = array_diff(array_unique(array_map('strtolower', $m[1])), $skip);
            if (!empty($candidates)) {
                $found = FlCountry::whereIn('code', $candidates)->first();
                if ($found) return $found->code;
            }
        }
        return null;
    }

    private array $referenceMap = [
        1=>'4life plus',2=>'tri factor',4=>'tf boost',6=>'riovida stix',
        8=>'pro-tf',10=>'pre/o biotics',25=>'collagen',47=>'energy go stix',75=>'riovida liquid',
    ];
    private array $categoryHints = [
        'immune'=>['immune','sistema inmunitario','transfer factor','tf '],
        'digestive'=>['digest','digestiva','biotics','pre/o','intestinal'],
        'energy'=>['energ','energy'],'skin'=>['collagen','colágeno','piel','belleza'],
        'weight'=>['peso','weight','transform'],'cardio'=>['cardio','heart','corazón'],
    ];
    private array $formatHints = [
        'capsules'=>['capsule','cápsula'],'tablets'=>['tablet','tableta'],
        'liquid'=>['liquid','líquido'],'powder'=>['powder','polvo','proteína'],
        'sachets'=>['sachet','stix'],'softgels'=>['softgel'],'spray'=>['spray'],
        'cream'=>['cream','crema'],'chewables'=>['chew','masticable'],
    ];

    private function parseProducts(string $html, string $baseUrl, int $countryId, string $currencyCode): array
    {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument(); $dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        $cards = $xpath->query("//div[contains(@class,'card') and @data-sku]");
        $products = [];
        foreach ($cards as $idx => $card) {
            $sku = $card->attributes->getNamedItem('data-sku')?->nodeValue;
            $a = $xpath->query(".//a[contains(@class,'card-link')]", $card)->item(0);
            $href = $a?->attributes?->getNamedItem('href')?->nodeValue;
            $url = $href ? (str_starts_with($href,'http') ? $href : rtrim($baseUrl,'/').'/'.ltrim($href,'/')) : null;
            $nameNode = $xpath->query(".//div[contains(@class,'product-name')]//div[1]", $card)->item(0);
            $name = $nameNode ? trim(html_entity_decode($nameNode->textContent)) : null;
            $img = $xpath->query(".//img[contains(@class,'product-img')]", $card)->item(0);
            $imgSrc = $img?->attributes?->getNamedItem('src')?->nodeValue ?: $img?->attributes?->getNamedItem('data-original')?->nodeValue;
            $descNode = $xpath->query(".//div[contains(@class,'tagline')]//p", $card)->item(0);
            $description = $descNode ? trim(html_entity_decode($descNode->textContent)) : null;
            $priceNode = $xpath->query(".//span[contains(@class,'green')]", $card)->item(0);
            $priceText = $priceNode ? trim($priceNode->textContent) : null;
            $digits = $priceText ? preg_replace('/[^0-9]/','',$priceText) : null;
            $price = ($digits && $digits !== '') ? (int)$digits : null;
            $hasFlash = $xpath->query(".//span[contains(@class,'flash-sale-flag')]", $card)->length > 0;
            $hasStrike = $xpath->query(".//span[contains(@class,'strikethrough')]", $card)->length > 0;
            $classAttr = $card->attributes->getNamedItem('class')?->nodeValue ?? '';
            $sizeNode = $xpath->query(".//input[@name='size']", $card)->item(0);
            $size = $sizeNode?->attributes?->getNamedItem('value')?->nodeValue;
            $h = mb_strtolower(trim(($name??'').' '.($description??'')));
            $cat = 'general'; foreach ($this->categoryHints as $c => $ks) { foreach ($ks as $k) { if ($k && str_contains($h,$k)) { $cat=$c; break 2; }}}
            $fmt = 'other'; foreach ($this->formatHints as $f => $ks) { foreach ($ks as $k) { if ($k && str_contains($h,$k)) { $fmt=$f; break 2; }}}
            $ref = null; if ($name) { $n=mb_strtolower($name); foreach ($this->referenceMap as $id=>$alias) { if (str_contains($n,$alias)) { $ref=$id; break; }}}
            $products[] = [
                'country_id'=>$countryId,'reference_id'=>$ref,'external_id'=>$sku,'name'=>$name,
                'slug'=>Str::slug($name??''),'url'=>$url,'image_url'=>$imgSrc?html_entity_decode($imgSrc):null,
                'description'=>$description?:($name??''),'price'=>$price,'price_wholesale'=>null,'price_loyalty'=>null,
                'currency_code'=>$currencyCode,'format'=>$fmt,'units_per_container'=>null,'serving_size'=>null,
                'is_pack'=>(is_string($size)&&mb_strtolower(trim($size))==='paquete'),
                'is_available'=>!str_contains($classAttr,'gray-bg'),
                'is_on_promotion'=>($hasFlash||$hasStrike),'promotion_details'=>$hasFlash?'Flash Sale':null,
                'category'=>$cat,'sort_order'=>$idx+1,
            ];
        }
        return $products;
    }
}
