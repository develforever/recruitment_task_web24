<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreImportRequest;
use App\Jobs\ProcessImportJob;
use App\Models\Import;
use App\Models\User;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Import::class);

        $perPage = (int) request()->query('per_page', 10);
        $perPage = min($perPage, 100);

        $imports = Import::with('user:id,name,email')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'data' => $imports,
        ]);
    }

    public function show(Import $import)
    {
        $this->authorize('view', $import);

        return response()->json([
            'data' => $import,
        ]);
    }

    public function showLogs(Import $import)
    {
        $this->authorize('viewLogs', $import);

        $perPage = (int) request()->query('per_page', 50);
        $perPage = min($perPage, 100);

        $logs = $import->logs()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'data' => $logs,
        ]);
    }

    public function store(StoreImportRequest $request)
    {
        $file = $request->file('file');
        $filePath = $file->store('imports');

        $import = Import::create([
            'file_name' => $file->getClientOriginalName(),
            'total_records' => 0,
            'successful_records' => 0,
            'failed_records' => 0,
            'user_id' => $request->user()->id,
            'status' => Import::STATUS_PROCESSING,
        ]);

        ProcessImportJob::dispatch($import, $filePath, $request->user());

        return response()->json([
            'message' => 'Import został dodany do kolejki przetwarzania.',
            'data' => $import,
        ], 201);
    }
}
