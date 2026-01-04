<?php

namespace Tests\Unit\Services\FileParser;

use App\Services\FileParser\JsonParser;
use PHPUnit\Framework\TestCase;

class JsonParserTest extends TestCase
{
    private JsonParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new JsonParser;
    }

    public function test_parse_valid_json_data(): void
    {
        $jsonData = <<<'JSON'
[
    {
        "transaction_id": "550e8400-e29b-41d4-a716-446655440000",
        "account_number": "PL12345678901234567890123456",
        "transaction_date": "2025-10-14",
        "amount": "150000",
        "currency": "PLN"
    },
    {
        "transaction_id": "550e8400-e29b-41d4-a716-446655440001",
        "account_number": "PL98765432109876543210987654",
        "transaction_date": "2025-10-13",
        "amount": "20050",
        "currency": "USD"
    }
]
JSON;

        $result = $this->parser->parse($jsonData);

        $this->assertCount(2, $result);
        $this->assertEquals('550e8400-e29b-41d4-a716-446655440000', $result[0]['transaction_id']);
        $this->assertEquals('PL12345678901234567890123456', $result[0]['account_number']);
        $this->assertEquals('2025-10-14', $result[0]['transaction_date']);
        $this->assertEquals('150000', $result[0]['amount']);
        $this->assertEquals('PLN', $result[0]['currency']);
    }

    public function test_parse_invalid_json_data(): void
    {
        $jsonData = <<<'JSON'
["invalid json":]
JSON;

        $result = $this->parser->parse($jsonData);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }
}
