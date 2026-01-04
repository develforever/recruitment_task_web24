<?php

namespace App\Services\FileParser;

class JsonParser extends AbstractParser
{
    protected string $extension = 'json';

    /**
     * @return array<int, array<string, mixed>>
     */
    public function parse(string $contents): array
    {
        return json_decode($contents, true) ?? [];
    }
}
