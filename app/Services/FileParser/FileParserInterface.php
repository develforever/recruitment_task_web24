<?php

namespace App\Services\FileParser;

interface FileParserInterface {
    public function parse(string $contents): array;
    public function supports(string $extension): bool;   
}