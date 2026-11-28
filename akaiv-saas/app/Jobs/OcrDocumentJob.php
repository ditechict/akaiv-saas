<?php

namespace App\Jobs;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use thiagoalessio\TesseractOCR\TesseractOCR;

class OcrDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 900;

    public function __construct(public Document $document) {}

    public function handle(): void
    {
        $document = $this->document->fresh();
        if (! $document) {
            return;
        }
        $this->document = $document;
        if ($this->document->status !== 'published' ||
            ! $this->document->virus_scanned || $this->document->virus_found) {
            return;
        }
        if (! $this->document->ocr_required || $this->document->ocr_completed) {
            return;
        }

        $disk = Storage::disk($this->document->storage_disk);
        if (! $disk->exists($this->document->storage_path)) {
            throw new \RuntimeException('OCR source document is missing.');
        }

        if (str_starts_with((string) $this->document->mime_type, 'text/')) {
            $this->document->updateQuietly([
                'extracted_text' => trim($disk->get($this->document->storage_path)),
                'ocr_completed' => true,
                'page_count' => 1,
            ]);
            return;
        }

        $directory = sys_get_temp_dir().'/akaiv-ocr-'.Str::uuid();
        File::makeDirectory($directory, 0700);
        try {
            $inputPath = $directory.'/input';
            file_put_contents($inputPath, $disk->get($this->document->storage_path));
            $pages = [$inputPath];
            if (strtolower((string) $this->document->file_extension) === 'pdf') {
                (new Process(['pdftoppm', '-png', $inputPath, $directory.'/page']))->mustRun();
                $pages = glob($directory.'/page-*.png');
                natsort($pages);
                if (! $pages) {
                    throw new \RuntimeException('PDF conversion produced no OCR pages.');
                }
            }
            $text = [];
            foreach ($pages as $page) {
                $text[] = trim((new TesseractOCR($page))->lang('eng')->run());
            }
            $this->document->updateQuietly([
                'extracted_text' => implode("\n\n", $text),
                'ocr_completed' => true,
                'page_count' => count($pages),
            ]);
        } finally {
            File::deleteDirectory($directory);
        }
    }
}
