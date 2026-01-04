<?php

namespace App\Services\FileParser;

interface FileParserInterface
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function parse(string $contents): array;

    public function supports(string $extension): bool;
}
