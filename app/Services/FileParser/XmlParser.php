<?php

namespace App\Services\FileParser;

class XmlParser extends AbstractParser
{
    protected string $extension = 'xml';

    public function parse(string $contents): array
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($contents, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($xml === false) {
            libxml_clear_errors();
            return [];
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

        libxml_clear_errors();

        return $records;
    }
}
