<?php

namespace Tests\Unit\Services;

use App\Services\ImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class ImportServiceTest extends TestCase
{

    use RefreshDatabase;
    private ImportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ImportService();
    }

    public function test_validate_row_with_invalid_account_number()
    {

         $row = [
            'transaction_id' => '550e8400-e29b-41d4-a716-446655440000',
            'account_number' => 'AU12345678901234567890123456',
            'transaction_date' => '2025-10-14',
            'amount' => '15000',
            'currency' => 'PLN',
            'import_id' => 1,
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Validation failed: The account number field format is invalid');

        $this->service->validateRow($row);

    }

    public function test_validate_row_with_invalid_amount()
    {

         $row = [
            'transaction_id' => '550e8400-e29b-41d4-a716-446655440000',
            'account_number' => 'PL12345678901234567890123456',
            'transaction_date' => '2025-10-14',
            'amount' => '150,000',
            'currency' => 'PLN',
            'import_id' => 1,
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Validation failed: The amount field must be a number.; The amount field format is invalid.');

        $this->service->validateRow($row);

    }

    public function test_validate_row_with_invalid_amount_format()
    {

         $row = [
            'transaction_id' => '550e8400-e29b-41d4-a716-446655440000',
            'account_number' => 'PL12345678901234567890123456',
            'transaction_date' => '2025-10-14',
            'amount' => '150.000',
            'currency' => 'PLN',
            'import_id' => 1,
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Validation failed: The amount field format is invalid');

        $this->service->validateRow($row);

    }

    public function test_validate_row_with_invalid_transaction_date()
    {

         $row = [
            'transaction_id' => '550e8400-e29b-41d4-a716-446655440000',
            'account_number' => 'PL12345678901234567890123456',
            'transaction_date' => '2025.1014',
            'amount' => '15000',
            'currency' => 'PLN',
            'import_id' => 1,
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Validation failed: The transaction date field must be a valid date.');

        $this->service->validateRow($row);

    }


    public function test_validate_row_with_invalid_transaction_id()
    {

         $row = [
            'transaction_id' => '550e8e29b4446655440000',
            'account_number' => 'PL12345678901234567890123456',
            'transaction_date' => '2025.1014',
            'amount' => '15000',
            'currency' => 'PLN',
            'import_id' => 1,
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Validation failed: The transaction id field must be a valid UUID.');

        $this->service->validateRow($row);

    }

    public function test_validate_parse_unsupported_extension()
    {

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unsupported file type "txt"');

        $this->service->parseRecords('txt', '');

    }


}
