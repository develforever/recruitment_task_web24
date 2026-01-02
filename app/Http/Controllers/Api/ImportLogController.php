<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Import;
use App\Models\ImportLog;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportLogController extends Controller
{
    /**
     * GET /api/imports/{import}/logs
     * Return paginated logs for given import.
     */
    public function index(Request $request, Import $import)
    {
        // Requires policy: $this->authorize('view', $import);
        $this->authorize('view', $import);

        $perPage = (int) $request->query('per_page', 15);

        $query = ImportLog::where('import_id', $import->id)
            ->select(['id','transaction_id','error_message','created_at'])
            ->orderBy('created_at', 'desc');

        return response()->json($query->paginate($perPage));
    }

    /**
     * GET /api/imports/{import}/logs/download
     * Stream CSV of all logs for given import.
     */
    public function download(Import $import): StreamedResponse
    {
        $this->authorize('view', $import);

        $fileName = sprintf('import-%s-logs-%s.csv', $import->id, now()->format('YmdHis'));

        $callback = function () use ($import) {
            $handle = fopen('php://output', 'w');

            // CSV header
            fputcsv($handle, ['transaction_id', 'error_message', 'created_at']);

            // Use chunk() to avoid memory issues on large exports
            ImportLog::where('import_id', $import->id)
                ->select(['transaction_id', 'error_message', 'created_at'])
                ->orderBy('created_at', 'desc')
                ->chunk(500, function ($rows) use ($handle) {
                    foreach ($rows as $row) {
                        fputcsv($handle, [
                            $row->transaction_id,
                            $row->error_message,
                            $row->created_at,
                        ]);
                    }
                });

            fclose($handle);
        };

        return response()->streamDownload($callback, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }
}