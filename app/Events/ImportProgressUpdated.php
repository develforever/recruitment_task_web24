<?php

namespace App\Events;

use App\Models\Import;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ImportProgressUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Import $import,
        public int $currentRecord,
        public int $totalRecords,
        public string $status,
        public ?string $lastError = null,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('import.' . $this->import->id),
            new PrivateChannel('import-progress'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'progress-updated';
    }

    public function broadcastWith(): array
    {
        return [
            'import_id' => $this->import->id,
            'current_record' => $this->currentRecord,
            'total_records' => $this->totalRecords,
            'percentage' => round(($this->currentRecord / $this->totalRecords) * 100),
            'status' => $this->status,
            'last_error' => $this->lastError,
            'successful_records' => $this->import->successful_records,
            'failed_records' => $this->import->failed_records,
        ];
    }
}