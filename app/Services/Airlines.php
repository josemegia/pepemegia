<?php // app Services Airlines.php

namespace App\Services;

use App\Parsers\Airlines\AirlineParserInterface;
use App\Parsers\Airlines\IberiaParser;
use App\Parsers\Airlines\CopaParser;
use Illuminate\Support\Str;

class Airlines
{
    public static function detectAndParse(string $pdfText): ?array
    {
        $parser = self::getParserFor($pdfText);

        return $parser?->parse($pdfText);
    }

    protected static function getParserFor(string $pdfText): ?AirlineParserInterface
    {
        $text = strtolower($pdfText);
        return match (true) {
            Str::contains($text, 'iberia') => new IberiaParser(),
            Str::contains($text, 'copa') => new CopaParser(),
            default => null,
        };
    }
    
    public static function iberia(string $pdfText): ?array
    {
        return (new \App\Parsers\Airlines\IberiaParser())->parse($pdfText);
    }
    
    public static function copa_airlines(string $pdfText): ?array
    {
        return (new \App\Parsers\Airlines\CopaParser())->parse($pdfText);
    }

}
