<?php

namespace App\Services\FileParser;

class CsvParser extends AbstractParser
{
    protected string $extension = 'csv';

    /**
     * @return array<int, array<string, string>>
     */
    public function parse(string $contents): array
    {
        $lines = array_filter(array_map('trim', explode("\n", $contents)));
        if (count($lines) === 0) {
            return [];
        }

        $rows = array_map('str_getcsv', $lines);
        $header = array_map('trim', array_shift($rows));

        $records = [];
        foreach ($rows as $row) {
            if (count($row) !== count($header)) {
                throw new \RuntimeException('Nieprawidłowa liczba kolumn w wierszu CSV.');
            }
            $records[] = array_combine($header, array_map('trim', $row));
        }

        return $records;
    }
}
