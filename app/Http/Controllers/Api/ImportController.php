<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Import;
use App\Models\ImportLog;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ImportController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Import::class);

        $perPage = (int) $request->query('per_page', 15);

        return response()->json(
            Import::orderBy('created_at', 'desc')->paginate($perPage)
        );
    }

    public function show(Import $import)
    {
        $this->authorize('view', $import);

        // Eager load a sample of logs + basic counts
        $import->load(['logs' => function ($q) { $q->orderBy('created_at', 'desc')->limit(50); }]);

        return response()->json($import);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Import::class);

        $request->validate([
            'file' => 'required|file',
        ]);

        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();
        $ext = strtolower($file->getClientOriginalExtension());

        $import = Import::create([
            'file_name' => $fileName,
            'total_records' => 0,
            'successful_records' => 0,
            'failed_records' => 0,
            'status' => 'processing',
        ]);

        $contents = file_get_contents($file->getRealPath());
        $records = [];

        try {
            if ($ext === 'csv') {
                $rows = array_map('str_getcsv', explode("\n", trim($contents)));
                $header = array_map('trim', array_shift($rows) ?? []);
                foreach ($rows as $row) {
                    if (count($row) < 1) continue;
                    $records[] = array_combine($header, $row);
                }
            } elseif ($ext === 'json') {
                $records = json_decode($contents, true) ?? [];
            } elseif ($ext === 'xml') {
                $xml = simplexml_load_string($contents, "SimpleXMLElement", LIBXML_NOCDATA);
                foreach ($xml->transaction ?? [] as $tx) {
                    $records[] = json_decode(json_encode($tx), true);
                }
            } else {
                return response()->json(['message' => 'Unsupported file type'], 422);
            }
        } catch (\Throwable $e) {
            $import->update(['status' => 'failed']);
            return response()->json(['message' => 'Failed to parse file', 'error' => $e->getMessage()], 422);
        }

        $total = count($records);
        $success = 0;
        $failed = 0;

        foreach ($records as $idx => $row) {
            $row = array_map(fn($v) => is_string($v) ? trim($v) : $v, $row);

            $validator = Validator::make($row, [
                'transaction_id'   => ['required', 'string'],
                'account_number'   => ['required', 'regex:/^PL\d{26}$/'], // simple PL IBAN example
                'transaction_date' => ['required', 'date'],
                'amount'           => ['required', 'numeric', 'gt:0'],
                'currency'         => ['required', 'regex:/^[A-Z]{3}$/'],
            ]);

            if ($validator->fails()) {
                ImportLog::create([
                    'import_id' => $import->id,
                    'transaction_id' => $row['transaction_id'] ?? Str::uuid(),
                    'error_message' => implode('; ', $validator->errors()->all()),
                ]);
                $failed++;
                continue;
            }

            $exists = Transaction::where('transaction_id', $row['transaction_id'])->exists();
            if ($exists) {
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

        $status = $failed === 0 ? 'success' : ($success === 0 ? 'failed' : 'partial');

        $import->update([
            'total_records' => $total,
            'successful_records' => $success,
            'failed_records' => $failed,
            'status' => $status,
        ]);

        return response()->json([
            'id' => $import->id,
            'total' => $total,
            'successful' => $success,
            'failed' => $failed,
            'status' => $status,
        ], 201);
    }
}