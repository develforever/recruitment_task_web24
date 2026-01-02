<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessImportJob;
use App\Models\Import;
use App\Models\User;
use Illuminate\Http\Request;

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

        $import->load(['logs' => function ($q) {
            $q->orderBy('created_at', 'desc')->limit(50);
        }]);

        return response()->json([
            'data' => $import,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Import::class);

        $request->validate([
            'file' => ['required', 'file', 'max:10240',  function ($attr, $file, $fail) {
                $ext = strtolower($file->getClientOriginalExtension());

                if (!in_array($ext, ['csv', 'json', 'xml'])) {
                    $fail('Nieobsługiwany format pliku.');
                    return;
                }

                if ($ext === 'xml') {
                    libxml_use_internal_errors(true);
                    $content = file_get_contents($file->getRealPath());
                    if (!simplexml_load_string($content)) {
                        $fail('Plik XML jest nieprawidłowy.');
                    }
                }
            }],
        ]);

        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();

        $filePath = $file->store('imports');
        $import = Import::create([
            'file_name' => $fileName,
            'total_records' => 0,
            'successful_records' => 0,
            'failed_records' => 0,
            'user_id' => $request->user()->id,
            'status' => Import::STATUS_PROCESSING,
        ]);

        ProcessImportJob::dispatch($import, $filePath, $request->user());

        return response()->json([
            'message' => 'Import queued successfully',
            'data' => $import,
        ], 201);
    }
}
