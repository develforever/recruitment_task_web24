<?php

namespace Tests\Feature\Http\Controller\Api;

use App\Jobs\ProcessImportJob;
use App\Models\Import;
use App\Models\User;
use App\Services\ImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_imports_unauthorized(): void
    {

        $response = $this->getJson(route('api.imports.index'));
        $response->assertUnauthorized();
    }

    public function test_imports_user_can_view(): void
    {

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson(route('api.imports.index'));
        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'current_page',
                'first_page_url',
                'last_page',
                'last_page_url',
                'links' => [
                    '*' => [
                        'url',
                        'label',
                        'active',
                    ],
                ],
                'next_page_url',
                'path',
                'per_page',
                'prev_page_url',
                'to',
                'total',
                'data' => [
                    '*' => [
                        'id',
                        'status',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ],
        ]);
    }

    public function test_file_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('api.imports.store'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_user_can_upload_csv(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $file = UploadedFile::fake()->createWithContent(
            'test.csv',
            <<<'CSV'
transaction_id,account_number,transaction_date,amount,currency
550e8400-e29b-41d4-a716-446655440000,PL12345678901234567890123456,2025-10-14,150000,PLN
550e8400-e29b-41d4-a716-446655440001,P98765432109876543210987654,2025-10-13,20050,USD
550e8400-e29b-41d4-a716-446655440002,PL11223344556677889900112233,2025-10-12,0,EUR
CSV
        );

        Bus::fake();

        $response = $this->actingAs($user)
            ->postJson(route('api.imports.store'), [
                'file' => $file,
            ]);

        $response->assertCreated();
        $response->assertJsonStructure([
            'message',
            'data' => [
                'id',
                'file_name',
                'total_records',
                'successful_records',
                'failed_records',
                'user_id',
                'status',
                'created_at',
                'updated_at',
            ],
        ]);
        $this->assertTrue(Storage::disk('local')->exists('imports'));

        Bus::assertDispatched(ProcessImportJob::class, function ($job) use ($user) {
            $this->assertTrue($job->user->is($user));
            $this->assertNotNull($job->import->id);
            $job->handle(app(ImportService::class));

            $this->assertEquals(3, Import::find($job->import->id)->total_records);
            $this->assertEquals(1, Import::find($job->import->id)->successful_records);
            $this->assertEquals(2, Import::find($job->import->id)->failed_records);

            return true;
        });
    }

    public function test_user_can_upload_xml(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $file = UploadedFile::fake()->createWithContent(
            'test.xml',
            <<<'XML'
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
    <amount>0</amount>
    <currency>USD</currency>
  </transaction>
</transactions>
XML
        );

        Bus::fake();

        $response = $this->actingAs($user)
            ->postJson(route('api.imports.store'), [
                'file' => $file,
            ]);

        $response->assertCreated();
        $response->assertJsonStructure([
            'message',
            'data' => [
                'id',
                'file_name',
                'total_records',
                'successful_records',
                'failed_records',
                'user_id',
                'status',
                'created_at',
                'updated_at',
            ],
        ]);
        $this->assertTrue(Storage::disk('local')->exists('imports'));

        Bus::assertDispatched(ProcessImportJob::class, function ($job) use ($user) {
            $this->assertTrue($job->user->is($user));
            $this->assertNotNull($job->import->id);
            $job->handle(app(ImportService::class));

            $this->assertEquals(2, Import::find($job->import->id)->total_records);
            $this->assertEquals(1, Import::find($job->import->id)->successful_records);
            $this->assertEquals(1, Import::find($job->import->id)->failed_records);

            return true;
        });
    }

    public function test_user_can_upload_json(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $file = UploadedFile::fake()->createWithContent(
            'test.json',
            <<<'JSON'
            [
  {
    "transaction_id": "550e8400-e29b-41d4-a716-446655440000",
    "account_number": "P12345678901234567890123456",
    "transaction_date": "2025-10-14",
    "amount": 150000,
    "currency": "PLN"
  },
  {
    "transaction_id": "550e8400-e29b-41d4-a716-446655440001",
    "account_number": "PL98765432109876543210987654",
    "transaction_date": "2025-10-13",
    "amount": 0,
    "currency": "USD"
  }
]
JSON
        );

        Bus::fake();

        $response = $this->actingAs($user)
            ->postJson(route('api.imports.store'), [
                'file' => $file,
            ]);

        $response->assertCreated();
        $response->assertJsonStructure([
            'message',
            'data' => [
                'id',
                'file_name',
                'total_records',
                'successful_records',
                'failed_records',
                'user_id',
                'status',
                'created_at',
                'updated_at',
            ],
        ]);
        $this->assertTrue(Storage::disk('local')->exists('imports'));

        Bus::assertDispatched(ProcessImportJob::class, function ($job) use ($user) {
            $this->assertTrue($job->user->is($user));
            $this->assertNotNull($job->import->id);
            $job->handle(app(ImportService::class));

            $this->assertEquals(2, Import::find($job->import->id)->total_records);
            $this->assertEquals(0, Import::find($job->import->id)->successful_records);
            $this->assertEquals(2, Import::find($job->import->id)->failed_records);

            return true;
        });
    }

    public function test_user_can_upload_file_with_not_supported_extension(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $file = UploadedFile::fake()->createWithContent(
            'test.txt',
            <<<'TXT'
TEST    
TXT
        );

        Bus::fake();

        $response = $this->actingAs($user)
            ->postJson(route('api.imports.store'), [
                'file' => $file,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
        $response->assertJsonStructure([
            'message',
            'errors' => [
                'file',
            ],
        ]);

        Bus::assertNotDispatched(ProcessImportJob::class);
    }
}
