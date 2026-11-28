<?php

namespace App\Jobs;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class IndexDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public Document $document) {}

    public function handle(): void
    {
        $document = $this->document->fresh();
        if (! $document) {
            return;
        }
        $this->document = $document;
        if (! $this->document) {
            return;
        }
        if (! $this->document->shouldBeSearchable()) {
            $this->document->unsearchable();
            return;
        }

        $this->document->searchable();
    }
}