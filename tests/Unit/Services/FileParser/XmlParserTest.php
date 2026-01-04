<?php

namespace Tests\Unit\Services\FileParser;

use App\Services\FileParser\XmlParser;
use PHPUnit\Framework\TestCase;

class XmlParserTest extends TestCase
{
    private XmlParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new XmlParser;
    }

    public function test_parse_valid_xml_data(): void
    {
        $xmlData = <<<'XML'
<transactions>
    <transaction>
        <transaction_id>550e8400-e29b-41d4-a716-446655440000</transaction_id>
        <account_number>PL12345678901234567890123456</account_number>
        <transaction_date>2025-10-14</transaction_date>
        <amount>150000</amount>
        <currency>PLN</currency>
    </transaction>
    <transaction>
        <transaction_id>550e8400-e29b-41d4-a716-446655440001</transaction_id>
        <account_number>PL98765432109876543210987654</account_number>
        <transaction_date>2025-10-13</transaction_date>
        <amount>20050</amount>
        <currency>USD</currency>
    </transaction>
</transactions>
XML;
        $result = $this->parser->parse($xmlData);

        $this->assertCount(2, $result);
        $this->assertEquals('550e8400-e29b-41d4-a716-446655440000', $result[0]['transaction_id']);
        $this->assertEquals('PL12345678901234567890123456', $result[0]['account_number']);
        $this->assertEquals('2025-10-14', $result[0]['transaction_date']);
        $this->assertEquals('150000', $result[0]['amount']);
        $this->assertEquals('PLN', $result[0]['currency']);
    }

    public function test_parse_invalid_xml_data(): void
    {
        $xmlData = <<<'XML'
<invalid><xml></invalid>
XML;
        $result = $this->parser->parse($xmlData);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }
}
