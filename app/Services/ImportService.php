<?php

namespace App\Services;

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
use Illuminate\Support\Str;

class ImportService
{

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

        $existingIds = $this->fetchExistingTransactionIds(array_column($records, 'transaction_id'));

        DB::transaction(function () use ($import, $total, $records, &$existingIds, &$success, &$failed) {
            foreach ($records as $idx => $row) {
                $row = array_map(fn($v) => is_string($v) ? trim($v) : $v, $row);
                $row['import_id'] = $import->id;

                $validated = $this->validateRow($row);

                if ($validated === null) {
                    $failed++;
                    continue;
                }

                if (isset($existingIds[$validated['transaction_id']])) {
                    Log::error('Import row failed', [
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
                DB::commit();
            }

            $status = $failed === 0 ? Import::STATUS_SUCCESS : ($success === 0 ? Import::STATUS_FAILED : Import::STATUS_PARTIAL);

            $import->update([
                'total_records' => $total,
                'successful_records' => $success,
                'failed_records' => $failed,
                'status' => $status,
            ]);

            return [
                'id' => $import->id,
                'total' => $total,
                'successful' => $success,
                'failed' => $failed,
                'status' => $status,
            ];
        });
    }

    private function parseRecords(string $ext, string $contents): array
    {
        return match ($ext) {
            'csv' => (new CsvParser())->parse($contents),
            'json' => (new JsonParser())->parse($contents),
            'xml' => (new XmlParser())->parse($contents),
            default => throw new \RuntimeException('Unsupported file type'),
        };
    }

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

    private function validateRow(array $row): array|null
    {
        $validator = Validator::make($row, [
            'transaction_id'   => ['required', 'string'],
            'account_number'   => ['required', 'regex:/^PL\d{26}$/'],
            'transaction_date' => ['required', 'date'],
            'amount'           => ['required', 'integer', 'min:1'],
            'currency'         => ['required', 'regex:/^[A-Z]{3}$/'],
        ]);

        if ($validator->fails()) {
            Log::error('Import row failed', [
                'import_id' => $row['import_id'] ?? null,
                'row' => $row,
                'error' => implode('; ', $validator->errors()->all()),
            ]);
            ImportLog::create([
                'import_id' => $row['import_id'] ?? null,
                'transaction_id' => $row['transaction_id'] ?? Str::uuid(),
                'error_message' => implode('; ', $validator->errors()->all()),
            ]);

            return null;
        }

        return $validator->validated();
    }
}
