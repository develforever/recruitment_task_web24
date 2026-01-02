<?php

namespace App\Services\FileParser;

class JsonParser implements FileParserInterface
{

    public function parse($contents): array
    {
        $records = json_decode($contents, true) ?? [];
        return $records;
    }

    public function supports($extension): bool
    {
        return strtolower($extension) === 'json';
    }
}
