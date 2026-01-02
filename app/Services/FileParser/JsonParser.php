<?php

namespace App\Services\FileParser;

class JsonParser extends AbstractParser
{
    protected string $extension = 'json';

    public function parse(string $contents): array
    {
        return json_decode($contents, true) ?? [];
    }
}
