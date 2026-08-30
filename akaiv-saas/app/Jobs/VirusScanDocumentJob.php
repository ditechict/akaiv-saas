<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use App\Models\Document;
use Rogervila\Clamav\Clamav;
use Exception;

class VirusScanDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 600;

    public function __construct(public Document $document) {}

    public function handle(): void
    {
        $path = $this->document->storage_path;
        $disk = Storage::disk($this->document->storage_disk);

        if (! $disk->exists($path)) {
            $this->document->updateQuietly([
                'status' => 'quarantined',
                'virus_scanned' => false,
            ]);
            return;
        }

        try {
            $stream = $disk->readStream($path);
            $clam = new Clamav(config('services.clamav.host'), config('services.clamav.port', 3310));
            $result = $clam->scanResourceStream($stream, $this->document->uuid);

            if (is_resource($stream)) {
                fclose($stream);
            }

            if ($result->isInfected()) {
                $this->document->updateQuietly([
                    'status' => 'quarantined',
                    'virus_scanned' => true,
                    'virus_found' => true,
                    'virus_scanned_at' => now(),
                    'metadata' => array_merge($this->document->metadata ?? [], [
                        'virus_signature' => $result->getMalwareName(),
                    ]),
                ]);
                activity()
                    ->on($this->document)
                    ->withProperties(['signature' => $result->getMalwareName()])
                    ->log('document.virus_detected');
                return;
            }
        } catch (Exception $e) {
            report($e);
            $this->document->updateQuietly([
                'status' => 'quarantined',
            ]);
            $this->release(300);
            return;
        }

        $this->document->updateQuietly([
            'virus_scanned' => true,
            'virus_found' => false,
            'virus_scanned_at' => now(),
            'status' => $this->document->status === 'uploading' ? 'published' : $this->document->status,
        ]);

        OcrDocumentJob::dispatch($this->document)->delay(now()->addSeconds(3));
        ThumbnailDocumentJob::dispatch($this->document)->delay(now()->addSeconds(5));
        IndexDocumentJob::dispatch($this->document)->delay(now()->addSeconds(10));
    }
}
