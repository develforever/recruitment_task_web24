<?php

namespace App\Services;

use App\Events\ImportProgressUpdated;
use App\Models\Import;
use App\Models\ImportLog;
use App\Models\Transaction;
use App\Models\User;
use App\Services\FileParser\CsvParser;
use App\Services\FileParser\JsonParser;
use App\Services\FileParser\XmlParser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ImportService
{
    /**
     * @throws \RuntimeException
     */
    public function processImport(Import $import, string $filePath, User $user): void
    {

        $fileName = $import->file_name;
        $contents = Storage::get($filePath);
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        try {
            $records = $this->parseRecords($ext, $contents);
        } catch (\Throwable $e) {
            Log::error('Import parsing failed', [
                'import_id' => $import->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $import->update(['status' => Import::STATUS_FAILED]);
            throw new \RuntimeException('Failed to parse file: ' . $e->getMessage(), previous: $e);
        }

        $total = count($records);
        $success = 0;
        $failed = 0;
        $status = $import->status;

        $import->update([
            'total_records' => $total,
        ]);

        ImportProgressUpdated::dispatch(
            $import,
            0,
            $total,
            Import::STATUS_PROCESSING,
            $success,
            $failed
        );

        $existingIds = $this->fetchExistingTransactionIds(array_column($records, 'transaction_id'));

        $batchSize = 100;
        $currentRecord = 0;

        foreach (array_chunk($records, $batchSize) as $batchIdx => $batch) {

            DB::transaction(function () use ($import, $total, $batch, &$existingIds, &$success, &$failed, &$currentRecord, $batchIdx, $batchSize) {

                foreach ($batch as $idx => $row) {
                    $currentRecord = $batchIdx * $batchSize + $idx + 1;

                    $row = array_merge($this->getDefaultRowShape(), $row);
                    $row = array_map(fn($v) => is_string($v) ? trim($v) : $v, $row);
                    $row['import_id'] = $import->id;

                    try {

                        $validated = $this->validateRow($row);
                    } catch (\Throwable $e) {

                        Log::error('Import row validation failed', [
                            'import_id' => $row['import_id'],
                            'row' => $row,
                            'error' => $e->getMessage(),
                        ]);
                        ImportLog::create([
                            'import_id' => $row['import_id'],
                            'transaction_id' => $row['transaction_id'],
                            'error_message' => $e->getMessage(),
                        ]);
                        $failed++;

                        continue;
                    }

                    if (isset($existingIds[$validated['transaction_id']])) {
                        Log::error('Import row transaction id exists', [
                            'import_id' => $import->id,
                            'row' => $row,
                            'error' => 'Duplicate transaction_id',
                        ]);
                        ImportLog::create([
                            'import_id' => $import->id,
                            'transaction_id' => $validated['transaction_id'],
                            'error_message' => 'Duplicate transaction_id',
                        ]);
                        $failed++;

                        continue;
                    }

                    Transaction::create([
                        'transaction_id' => $validated['transaction_id'],
                        'account_number' => $validated['account_number'],
                        'transaction_date' => $validated['transaction_date'],
                        'amount' => $validated['amount'],
                        'currency' => $validated['currency'],
                    ]);

                    $existingIds[$validated['transaction_id']] = true;
                    $success++;
                    $import->update([
                        'successful_records' => $success,
                        'failed_records' => $failed,
                    ]);

                    if (($currentRecord + 1) % 10 === 0) {
                        ImportProgressUpdated::dispatch(
                            $import,
                            $success + $failed,
                            $total,
                            Import::STATUS_PROCESSING,
                            $success,
                            $failed
                        );
                    }
                }
            });
        }

        $status = $failed === 0 ? Import::STATUS_SUCCESS : ($success === 0 ? Import::STATUS_FAILED : Import::STATUS_PARTIAL);

        $import->update([
            'total_records' => $total,
            'successful_records' => $success,
            'failed_records' => $failed,
            'status' => $status,
        ]);

        ImportProgressUpdated::dispatch(
            $import,
            $total,
            $total,
            $status,
            $success,
            $failed
        );

        Storage::delete($filePath);
    }

    /**
     * @return array<int, array<string, mixed>>
     *
     * @throws \RuntimeException
     */
    public function parseRecords(string $ext, string $contents): array
    {
        return match ($ext) {
            'csv' => (new CsvParser)->parse($contents),
            'json' => (new JsonParser)->parse($contents),
            'xml' => (new XmlParser)->parse($contents),
            default => throw new \RuntimeException('Unsupported file type "' . $ext . '"'),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function getDefaultRowShape(): array
    {
        return [
            'transaction_id' => null,
            'account_number' => null,
            'transaction_date' => null,
            'amount' => null,
            'currency' => null,
            'import_id' => null,
        ];
    }

    /**
     * @param  array<int, string>  $transactionIds
     * @return array<string, bool>
     */
    private function fetchExistingTransactionIds(array $transactionIds): array
    {
        $ids = [];
        foreach (array_chunk(array_filter($transactionIds), 500) as $chunk) {
            Transaction::whereIn('transaction_id', $chunk)
                ->pluck('transaction_id')
                ->each(function ($id) use (&$ids) {
                    $ids[$id] = true;
                });
        }

        return $ids;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public function validateRow(array $row): array
    {
        $validator = Validator::make($row, [
            'transaction_id' => ['required', 'uuid:4'],
            'account_number' => ['required', 'regex:/^PL\d{26}$/'],
            'transaction_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'regex:/^\d+$/', 'min:1'],
            'currency' => ['required', 'regex:/^[A-Z]{3}$/'],
        ]);

        if ($validator->stopOnFirstFailure()->fails()) {
            throw new \RuntimeException('Validation failed: ' . implode('; ', $validator->errors()->all()));
        }

        return $validator->validated();
    }
}
