<?php

namespace App\Jobs;

use App\Models\Document;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Socket\Raw\Factory;
use Xenolope\Quahog\Client;

class VirusScanDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 600;

    public function __construct(public Document $document) {}

    public function handle(): void
    {
        $document = $this->document->fresh();
        if (! $document) {
            return;
        }
        $this->document = $document;
        if ($this->document->status === 'deleted') {
            return;
        }
        $path = $this->document->storage_path;
        $disk = Storage::disk($this->document->storage_disk);

        if (! $disk->exists($path)) {
            $this->document->updateQuietly([
                'status' => 'quarantined',
                'virus_scanned' => false,
            ]);

            return;
        }

        $stream = null;
        try {
            $stream = $disk->readStream($path);
            $socket = (new Factory)->createClient(sprintf(
                'tcp://%s:%d',
                config('services.clamav.host'),
                config('services.clamav.port', 3310),
            ));
            $clam = new Client($socket, 30, PHP_NORMAL_READ);
            $result = $clam->scanResourceStream($stream);

            if ($result->isFound()) {
                $this->document->updateQuietly([
                    'status' => 'quarantined',
                    'virus_scanned' => true,
                    'virus_found' => true,
                    'virus_scanned_at' => now(),
                    'metadata' => array_merge($this->document->metadata ?? [], [
                        'virus_signature' => $result->getReason(),
                    ]),
                ]);
                activity()
                    ->on($this->document)
                    ->withProperties(['signature' => $result->getReason()])
                    ->log('document.virus_detected');

                return;
            }
            if (! $result->isOk()) {
                throw new \RuntimeException($result->getReason() ?? 'ClamAV returned an unsuccessful result.');
            }
        } catch (Exception $e) {
            report($e);
            $this->document->updateQuietly([
                'status' => 'quarantined',
                'virus_scanned' => false,
            ]);
            $this->release(300);

            return;
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        $this->document->updateQuietly([
            'virus_scanned' => true,
            'virus_found' => false,
            'virus_scanned_at' => now(),
            'status' => $this->document->status === 'uploading' ? 'published' : $this->document->status,
        ]);

        Bus::chain([
            new OcrDocumentJob($this->document),
            new ThumbnailDocumentJob($this->document),
            new IndexDocumentJob($this->document),
        ])->dispatch();
    }
}
