<?php

namespace App\Jobs;

use App\Models\Import;
use App\Models\User;
use App\Services\ImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessImportJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public Import $import, public string $filePath, public User $user)
    {
        
    }

    /**
     * Execute the job.
     */
    public function handle(ImportService $service): void
    {
        $service->processImport($this->import, $this->filePath, $this->user);
    }
}
