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

        DB::transaction(function () use ($import, $filePath, $user) {

            $fileName = $import->file_name;
            $contents = $content = Storage::get($filePath);
            $records = [];
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));


            try {
                if ($ext === 'csv') {
                    $records = (new CsvParser())->parse($contents);
                } elseif ($ext === 'json') {
                    $records = (new JsonParser())->parse($contents);
                } elseif ($ext === 'xml') {
                    $records = (new XmlParser())->parse($contents);
                } else {
                    throw new \Exception('Unsupported file type');
                }
            } catch (\Throwable $e) {
                Log::error('Import parsing failed', [
                    'import_id' => $import->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $import->update(['status' => Import::STATUS_FAILED]);
                throw new \Exception('Failed to parse file: ' . $e->getMessage());
            }

            $total = count($records);
            $success = 0;
            $failed = 0;

            $existingIds = Transaction::whereIn(
                'transaction_id',
                array_column($records, 'transaction_id')
            )->pluck('transaction_id')->toArray();

            foreach ($records as $idx => $row) {
                $row = array_map(fn($v) => is_string($v) ? trim($v) : $v, $row);

                $validator = Validator::make($row, [
                    'transaction_id' => ['required', 'string', 'unique:transactions,transaction_id'],
                    'account_number'   => ['required', 'regex:/^PL\d{26}$/'],
                    'transaction_date' => ['required', 'date'],
                    'amount'           => ['required', 'integer', 'min:1'],
                    'currency'         => ['required', 'regex:/^[A-Z]{3}$/'],
                ]);

                if ($validator->fails()) {
                    Log::error('Import row failed', [
                        'import_id' => $import->id,
                        'row' => $row,
                        'error' => implode('; ', $validator->errors()->all()),
                    ]);
                    ImportLog::create([
                        'import_id' => $import->id,
                        'transaction_id' => $row['transaction_id'] ?? Str::uuid(),
                        'error_message' => implode('; ', $validator->errors()->all()),
                    ]);
                    $failed++;
                    continue;
                }


                if (in_array($row['transaction_id'], $existingIds)) {
                    Log::error('Import row failed', [
                        'import_id' => $import->id,
                        'row' => $row,
                        'error' => 'Duplicate transaction_id',
                    ]);
                    ImportLog::create([
                        'import_id' => $import->id,
                        'transaction_id' => $row['transaction_id'],
                        'error_message' => 'Duplicate transaction_id',
                    ]);
                    $failed++;
                    continue;
                }

                Transaction::create([
                    'transaction_id' => $row['transaction_id'],
                    'account_number' => $row['account_number'],
                    'transaction_date' => $row['transaction_date'],
                    'amount' => $row['amount'],
                    'currency' => $row['currency'],
                ]);

                $success++;
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
}
