<?php

use App\Models\Document;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    Organization::create([
        'name' => 'Default Court',
        'slug' => 'default',
        'contact_email' => 'default@example.test',
    ]);
});

function legacyFixture(): string
{
    $root = sys_get_temp_dir().'/akaiv-legacy-'.uniqid();
    mkdir($root.'/John Doe/Judgments', 0777, true);
    file_put_contents($root.'/John Doe/Judgments/2021-01-23_23_28_26_Judgment.txt', 'legacy judgment');
    file_put_contents($root.'/John Doe/Judgments/evil.php', '<?php echo "pwned";');
    file_put_contents($root.'/John Doe/Judgments/.htaccess', 'Require all denied');

    return $root;
}

it('does not write documents during a dry run', function (): void {
    Storage::fake('s3');
    $root = legacyFixture();

    $this->artisan('app:migrate-legacy-documents', [
        '--dry-run' => true,
        '--legacy-files' => $root,
        '--target-org-slug' => 'default',
    ])->assertSuccessful();

    expect(Document::withoutTenancy()->count())->toBe(0);
});

it('skips PHP executable files and imports documents', function (): void {
    Storage::fake('s3');
    $root = legacyFixture();

    $this->artisan('app:migrate-legacy-documents', [
        '--legacy-files' => $root,
        '--target-org-slug' => 'default',
    ])->assertSuccessful();

    $documents = Document::withoutTenancy()->get();

    expect($documents)->toHaveCount(1)
        ->and($documents->first()->original_filename)->toContain('Judgment.txt')
        ->and($documents->first()->status)->toBe('published');
});

it('parses the embedded timestamp into created_at', function (): void {
    Storage::fake('s3');
    $root = legacyFixture();

    $this->artisan('app:migrate-legacy-documents', [
        '--legacy-files' => $root,
        '--target-org-slug' => 'default',
    ])->assertSuccessful();

    $document = Document::withoutTenancy()->first();

    expect($document->created_at->format('Y-m-d'))->toBe('2021-01-23');
});
