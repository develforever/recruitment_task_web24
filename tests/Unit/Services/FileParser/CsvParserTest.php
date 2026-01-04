<?php

namespace Tests\Unit\Services\FileParser;

use App\Services\FileParser\CsvParser;
use PHPUnit\Framework\TestCase;

class CsvParserTest extends TestCase
{
    private CsvParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new CsvParser();
    }

    
    public function test_parse_valid_csv_data(): void
    {
        $csvData = <<<CSV
transaction_id,account_number,transaction_date,amount,currency
550e8400-e29b-41d4-a716-446655440000,PL12345678901234567890123456,2025-10-14,150000,PLN
550e8400-e29b-41d4-a716-446655440001,PL98765432109876543210987654,2025-10-13,20050,USD
CSV;

        $result = $this->parser->parse($csvData);

        $this->assertCount(2, $result);
        $this->assertEquals('550e8400-e29b-41d4-a716-446655440000', $result[0]['transaction_id']);
        $this->assertEquals('PL12345678901234567890123456', $result[0]['account_number']);
        $this->assertEquals('2025-10-14', $result[0]['transaction_date']);
        $this->assertEquals('150000', $result[0]['amount']);
        $this->assertEquals('PLN', $result[0]['currency']);
    }


    public function test_parse_single_record(): void
    {
        $csvData = <<<CSV
transaction_id,account_number,transaction_date,amount,currency
550e8400-e29b-41d4-a716-446655440000,PL12345678901234567890123456,2025-10-14,150000,PLN
CSV;

        $result = $this->parser->parse($csvData);

        $this->assertCount(1, $result);
        $this->assertArrayHasKey('transaction_id', $result[0]);
        $this->assertArrayHasKey('account_number', $result[0]);
    }


    public function test_parse_csv_trims_whitespace(): void
    {
        $csvData = <<<CSV
transaction_id, account_number,transaction_date, amount,currency
  550e8400-e29b-41d4-a716-446655440000  ,  PL12345678901234567890123456  ,  2025-10-14  ,  150000  ,  PLN  
CSV;

        $result = $this->parser->parse($csvData);

        $this->assertEquals('550e8400-e29b-41d4-a716-446655440000', $result[0]['transaction_id']);
        $this->assertEquals('PL12345678901234567890123456', $result[0]['account_number']);
    }


    public function test_parse_csv_ignores_empty_lines(): void
    {
        $csvData = <<<CSV
transaction_id,account_number,transaction_date,amount,currency
550e8400-e29b-41d4-a716-446655440000,PL12345678901234567890123456,2025-10-14,150000,PLN

550e8400-e29b-41d4-a716-446655440001,PL98765432109876543210987654,2025-10-13,20050,USD

CSV;

        $result = $this->parser->parse($csvData);

        $this->assertCount(2, $result);
    }


    public function test_parse_csv_throws_exception_on_invalid_columns(): void
    {
        $csvData = <<<CSV
transaction_id,account_number,transaction_date,amount,currency
550e8400-e29b-41d4-a716-446655440000,PL12345678901234567890123456,2025-10-14,150000
CSV;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Nieprawidłowa liczba kolumn');

        $this->parser->parse($csvData);
    }


    public function test_parse_csv_with_only_headers(): void
    {
        $csvData = <<<CSV
transaction_id,account_number,transaction_date,amount,currency
CSV;

        $result = $this->parser->parse($csvData);

        $this->assertCount(0, $result);
    }


    public function test_parse_csv_preserves_header_case(): void
    {
        $csvData = <<<CSV
transaction_id,account_number,transaction_date,amount,currency
550e8400-e29b-41d4-a716-446655440000,PL12345678901234567890123456,2025-10-14,150000,PLN
CSV;

        $result = $this->parser->parse($csvData);

        $keys = array_keys($result[0]);
        $this->assertContains('transaction_id', $keys);
        $this->assertContains('account_number', $keys);
    }


    public function test_parse_csv_returns_string_types(): void
    {
        $csvData = <<<CSV
transaction_id,account_number,transaction_date,amount,currency
550e8400-e29b-41d4-a716-446655440000,PL12345678901234567890123456,2025-10-14,150000,PLN
CSV;

        $result = $this->parser->parse($csvData);

        $this->assertIsString($result[0]['transaction_id']);
        $this->assertIsString($result[0]['amount']);
        $this->assertIsString($result[0]['currency']);
    }


    public function test_parse_csv_with_special_characters(): void
    {
        $csvData = <<<CSV
transaction_id,account_number,transaction_date,amount,currency
550e8400-e29b-41d4-a716-446655440000,PL12345678901234567890123456,2025-10-14,"150,000",PLN
CSV;

        $result = $this->parser->parse($csvData);

        $this->assertEquals('"150,000"', $result[0]['amount']);
    }


    public function test_parse_csv_with_many_records(): void
    {
        $records = ["transaction_id,account_number,transaction_date,amount,currency"];

        for ($i = 0; $i < 100; $i++) {
            $records[] = "550e8400-e29b-41d4-a716-44665544000{$i},PL12345678901234567890123456,2025-10-14,150000,PLN";
        }

        $csvData = implode("\n", $records);
        $result = $this->parser->parse($csvData);

        $this->assertCount(100, $result);
    }

    public function test_parse_csv_empty_string(): void
    {
        $result = $this->parser->parse('');

        $this->assertCount(0, $result);
    }
}
