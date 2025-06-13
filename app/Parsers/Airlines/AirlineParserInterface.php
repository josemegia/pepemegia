<?php // app/Parsers/Airlines/AirlineParserInterface.php

namespace App\Parsers\Airlines;

interface AirlineParserInterface
{
    public function parse(string $pdfText): ?array;
}
