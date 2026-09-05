<?php

namespace App\Observers;

use App\Jobs\VirusScanDocumentJob;
use App\Models\Document;

class DocumentObserver
{
    public function created(Document $document): void
    {
        VirusScanDocumentJob::dispatch($document);
    }
}