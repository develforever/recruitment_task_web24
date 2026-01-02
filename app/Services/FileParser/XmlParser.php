<?php


namespace App\Services\FileParser;

class XmlParser implements FileParserInterface
{

    public function parse($contents): array
    {
        $records = [];
        $xml = simplexml_load_string($contents, "SimpleXMLElement", LIBXML_NOCDATA);
        foreach ($xml->transaction ?? [] as $tx) {
            $records[] = json_decode(json_encode($tx), true);
        }
        return $records;
    }

    public function supports($extension): bool
    {
        return strtolower($extension) === 'xml';
    }
}
