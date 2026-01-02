<?php

namespace App\Services\FileParser;

class CsvParser implements FileParserInterface
{

    public function parse($contents): array
    {
        $records = [];
        $rows = array_map('str_getcsv', explode("\n", trim($contents)));
        $header = array_map('trim', array_shift($rows) ?? []);
        foreach ($rows as $row) {
            if (count($row) < 1) continue;
            $records[] = array_combine($header, $row);
        }
        return $records;
    }

    public function supports($extension): bool
    {
        return strtolower($extension) === 'csv';
    }
}
