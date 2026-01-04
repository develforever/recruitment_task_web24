<?php

namespace App\Services\FileParser;

class XmlParser extends AbstractParser
{
    protected string $extension = 'xml';

    public function parse(string $contents): array
    {
        $xml = simplexml_load_string($contents, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($xml === false) {
            throw new \RuntimeException('Nie można odczytać pliku XML.');
        }

        $records = [];
        foreach ($xml->transaction ?? [] as $tx) {
            $records[] = [
                'transaction_id' => (string) ($tx->transaction_id ?? ''),
                'account_number' => (string) ($tx->account_number ?? ''),
                'transaction_date' => (string) ($tx->transaction_date ?? ''),
                'amount' => (string) ($tx->amount ?? ''),
                'currency' => (string) ($tx->currency ?? ''),
            ];
        }

        return $records;
    }
}
