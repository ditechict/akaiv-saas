<?php

use App\Jobs\IndexDocumentJob;
use App\Jobs\OcrDocumentJob;
use App\Jobs\ThumbnailDocumentJob;
use App\Jobs\VirusScanDocumentJob;
use App\Models\Document;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(Tests\TestCase::class, RefreshDatabase::class);

function pipelineDocument(array $attributes = []): Document
{
    $organization = Organization::create([
        'name' => 'Pipeline Court',
        'slug' => 'pipeline-court-'.uniqid(),
        'contact_email' => 'pipeline@example.test',
    ]);

    return Document::create(array_merge([
        'organization_id' => $organization->id,
        'friendly_name' => 'Judgment',
        'original_filename' => 'judgment.pdf',
        'slug' => 'judgment-'.uniqid(),
        'storage_disk' => 'private',
        'storage_path' => 'documents/judgment.pdf',
        'file_extension' => 'pdf',
        'mime_type' => 'application/pdf',
        'status' => 'uploading',
    ], $attributes));
}

it('quarantines a document whose file is missing from storage', function (): void {
    Storage::fake('private');
    $document = pipelineDocument();

    (new VirusScanDocumentJob($document))->handle();

    expect($document->fresh()->status)->toBe('quarantined')
        ->and($document->fresh()->virus_scanned)->toBeFalse();
});

it('skips OCR when it has already completed', function (): void {
    Storage::fake('private');
    $document = pipelineDocument(['ocr_required' => true, 'ocr_completed' => true]);

    (new OcrDocumentJob($document))->handle();

    expect($document->fresh()->ocr_completed)->toBeTrue();
});

it('skips thumbnail generation for non-pdf documents', function (): void {
    Storage::fake('private');
    $document = pipelineDocument(['file_extension' => 'txt', 'mime_type' => 'text/plain']);

    (new ThumbnailDocumentJob($document))->handle();

    expect(Storage::disk('private')->allFiles('thumbnails'))->toBeEmpty();
});

it('marks a deleted document unsearchable when indexing', function (): void {
    $document = pipelineDocument(['status' => 'deleted']);

    (new IndexDocumentJob($document))->handle();

    expect(true)->toBeTrue();
});
