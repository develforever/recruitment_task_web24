<?php

namespace App\Services\FileParser;

abstract class AbstractParser implements FileParserInterface
{
    protected string $extension;

    public function supports(string $extension): bool
    {
        return strtolower($extension) === $this->extension;
    }

    abstract public function parse(string $contents): array;
}
