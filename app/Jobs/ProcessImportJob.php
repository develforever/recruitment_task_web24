<?php

namespace App\Jobs;

use App\Events\ImportProgressUpdated;
use App\Models\Import;
use App\Models\User;
use App\Services\ImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 600;

    public int $maxExceptions = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Import $import,
        public string $filePath,
        public User $user
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ImportService $service): void
    {
        try {
            $service->processImport($this->import, $this->filePath, $this->user);
        } catch (Throwable $e) {
            Log::error('Import processing failed in job', [
                'import_id' => $this->import->id,
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->import->update([
                'status' => Import::STATUS_FAILED,
            ]);

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Import job failed permanently', [
            'import_id' => $this->import->id,
            'user_id' => $this->user->id,
            'error' => $exception->getMessage(),
        ]);

        $this->import->update([
            'status' => Import::STATUS_FAILED,
        ]);

        ImportProgressUpdated::dispatch(
            $this->import,
            0,
            $this->import->total_records,
            Import::STATUS_FAILED,
            $this->import->successful_records ?? 0,
            $this->import->failed_records ?? 0,
            $exception->getMessage()
        );
    }
}
