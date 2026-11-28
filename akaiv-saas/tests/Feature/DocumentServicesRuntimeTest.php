<?php

use App\Jobs\IndexDocumentJob;
use App\Jobs\OcrDocumentJob;
use App\Jobs\ThumbnailDocumentJob;
use App\Jobs\VirusScanDocumentJob;
use App\Models\Document;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    if (! getenv('AKAIV_SERVICE_TESTS')) {
        $this->markTestSkipped('Enable AKAIV_SERVICE_TESTS with ClamAV, Poppler and Tesseract available.');
    }
    Queue::fake();
    Storage::fake('private');
});

function runtimeDocument(): Document
{
    $org = Organization::create([
        'name' => 'Runtime Court',
        'slug' => 'runtime-court',
        'contact_email' => 'runtime@example.test',
    ]);
    return Document::create([
        'organization_id' => $org->id,
        'friendly_name' => 'Runtime Court Record',
        'original_filename' => 'runtime.pdf',
        'slug' => 'runtime-record',
        'storage_disk' => 'private',
        'storage_path' => 'documents/runtime.pdf',
        'file_extension' => 'pdf',
        'mime_type' => 'application/pdf',
        'status' => 'uploading',
        'ocr_required' => true,
    ]);
}

it('scans a clean PDF using ClamAV and dispatches processing jobs', function (): void {
    $document = runtimeDocument();
    Storage::disk('private')->put($document->storage_path, file_get_contents('/tmp/akaiv-runtime.pdf'));

    (new VirusScanDocumentJob($document))->handle();

    expect($document->fresh()->virus_scanned)->toBeTrue()
        ->and($document->fresh()->virus_found)->toBeFalse()
        ->and($document->fresh()->status)->toBe('published');
    Queue::assertPushedWithChain(OcrDocumentJob::class, [ThumbnailDocumentJob::class, IndexDocumentJob::class]);
});

it('extracts PDF text through Poppler and Tesseract', function (): void {
    $document = runtimeDocument();
    Storage::disk('private')->put($document->storage_path, file_get_contents('/tmp/akaiv-runtime.pdf'));

    $document->updateQuietly(['status' => 'published', 'virus_scanned' => true]);
    (new OcrDocumentJob($document))->handle();

    expect($document->fresh()->ocr_completed)->toBeTrue()
        ->and(strtoupper($document->fresh()->extracted_text))->toContain('AKAIV COURT RECORD')
        ->and(strtoupper($document->fresh()->extracted_text))->toContain('SECOND PAGE EVIDENCE')
        ->and($document->fresh()->page_count)->toBe(2);
});

it('creates a valid PNG thumbnail through Poppler', function (): void {
    $document = runtimeDocument();
    Storage::disk('private')->put($document->storage_path, file_get_contents('/tmp/akaiv-runtime.pdf'));

    (new ThumbnailDocumentJob($document))->handle();

    $bytes = Storage::disk('private')->get('thumbnails/'.$document->uuid.'.png');
    expect(substr($bytes, 0, 8))->toBe("\x89PNG\r\n\x1a\n");
    expect(getimagesizefromstring($bytes))->not->toBeFalse();
});
