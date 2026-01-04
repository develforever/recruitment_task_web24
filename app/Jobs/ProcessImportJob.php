<?php

namespace App\Jobs;

use App\Models\Import;
use App\Models\User;
use App\Services\ImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessImportJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 300;

    public $maxExceptions = 3;

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

        // $this->user->notify(new ImportFailedNotification($this->import));
    }
}
