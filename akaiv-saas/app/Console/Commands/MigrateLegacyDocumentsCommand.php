<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Organization;
use App\Models\Document as NewDocument;
use App\Models\Folder as NewFolder;
use App\Models\User as NewUser;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MigrateLegacyDocumentsCommand extends Command
{
    protected $signature = 'app:migrate-legacy-documents
                            {--dry-run : Show plan only, do not move or insert}
                            {--legacy-db= : PDO DSN of legacy MySQL}
                            {--legacy-user=root : legacy username}
                            {--legacy-pass= : legacy password}
                            {--legacy-files= : absolute path to legacy public/documents dir}
                            {--target-org-slug=default : slug of target organization}';

    protected $description = 'One-shot migration: legacy myarchivesonline.com (Laravel 6) → new SaaS (Laravel 11)';

    public function handle(): int
    {
        $dry = $this->option('dry-run');
        $legacyRoot = rtrim($this->option('legacy-files'), '/');
        $org = Organization::where('slug', $this->option('target-org-slug'))->firstOrFail();
        $failures = collect();
        $migrated = 0;
        $total = 0;

        $this->info("Starting migration" . ($dry ? ' (DRY RUN)' : '') . " for org: {$org->name}");
        $this->info("Legacy files at: {$legacyRoot}");

        try {
            $pdo = DB::connection('legacy_mysql')->getPdo();
        } catch (\Throwable $e) {
            $this->warn("Could not reach legacy DB: {$e->getMessage()}. Working from filesystem only.");
            $pdo = null;
        }

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($legacyRoot));
        foreach ($iterator as $file) {
            if ($file->isDir() || $file->getBasename() === '.htaccess' || $file->getExtension() === '') {
                continue;
            }
            if (in_array(strtolower($file->getExtension()), ['php', 'phtml', 'phar'], true)) {
                $failures->push((string)$file . ' | skipped: PHP executable');
                continue;
            }
            $total++;

            $relPath = Str::after(str_replace('\\', '/', $file->getPathname()), $legacyRoot . '/');
            $parts = explode('/', $relPath);
            $legacyUsername = urldecode($parts[0] ?? 'UNKNOWN');
            $legacyFolderName = isset($parts[2]) ? urldecode($parts[1] ?? 'General') : null;
            $legacyFilename = basename($relPath);

            $timestamp = null;
            $cleanName = $legacyFilename;
            if (preg_match('/^(\d{4}-\d{2}-\d{2})_(\d{2})_(\d{2})_(\d{2})_(.*)\.([a-zA-Z0-9]+)$/', $legacyFilename, $m)) {
                $timestamp = Carbon::parse("{$m[1]} {$m[2]}:{$m[3]}:{$m[4]}");
                $cleanName = str_replace('_', ' ', $m[5]) . '.' . $m[6];
            }

            $user = $pdo ? $this->matchUserByName($pdo, $legacyUsername) : null;
            $folder = $legacyFolderName ? $this->findOrCreateFolder($org, $user, $legacyFolderName, $dry) : null;

            $this->line(sprintf(
                '  %-3s %-50s owner=%-25s folder=%s',
                $dry ? '[DRY]' : '[OK]',
                Str::limit($cleanName, 50),
                $legacyUsername,
                $legacyFolderName ?? '(none)'
            ));

            if ($dry) {
                $migrated++;
                continue;
            }

            try {
                $uuid = Str::uuid();
                $ext = $file->getExtension();
                $newKey = "org_{$org->id}/docs/{$uuid}/{$uuid}." . strtolower($ext);
                $disk = Storage::disk(config('filesystems.default'));
                $disk->put($newKey, file_get_contents($file->getPathname()), 'private');
                $sha = hash_file('sha256', $file->getPathname());
                $dup = NewDocument::where('organization_id', $org->id)->where('sha256_checksum', $sha)->first();

                NewDocument::create([
                    'uuid' => $uuid,
                    'organization_id' => $org->id,
                    'folder_id' => $folder?->id,
                    'owner_id' => $user?->id,
                    'uploaded_by' => $user?->id,
                    'friendly_name' => pathinfo($cleanName, PATHINFO_FILENAME),
                    'original_filename' => $legacyFilename,
                    'slug' => Str::slug(pathinfo($cleanName, PATHINFO_FILENAME)) . '-' . substr($uuid, 0, 8),
                    'storage_disk' => config('filesystems.default'),
                    'storage_path' => $newKey,
                    'size_bytes' => $file->getSize(),
                    'mime_type' => mime_content_type($file->getPathname()) ?: null,
                    'file_extension' => strtolower($ext),
                    'sha256_checksum' => $sha,
                    'folio_number' => null,
                    'description' => 'Migrated from legacy app on ' . now()->toIso8601String() . '. Legacy path: ' . $relPath,
                    'status' => 'published',
                    'virus_scanned' => false,
                    'created_at' => $timestamp ?? $file->getCTime(),
                    'updated_at' => $file->getMTime(),
                ]);

                $migrated++;
            } catch (\Throwable $e) {
                $failures->push($relPath . ' | ' . $e->getMessage());
                report($e);
            }
        }

        $this->newLine(2);
        $this->info("Scan complete: {$migrated}/{$total} migrated successfully.");
        if ($failures->isNotEmpty()) {
            $outPath = storage_path('logs/migration-failures-' . now()->format('Ymd-His') . '.csv');
            $failures->each(fn($l) => file_put_contents($outPath, $l . PHP_EOL, FILE_APPEND));
            $this->warn("Failures: {$failures->count()}. Report: {$outPath}");
        }
        return self::SUCCESS;
    }

    private function matchUserByName(\PDO $pdo, string $legacyUsername): ?NewUser
    {
        $parts = preg_split('/\s+/', trim($legacyUsername), 2);
        $stmt = $pdo->prepare(
            'SELECT id, email, name, surname, role FROM users WHERE name LIKE ? AND surname LIKE ? LIMIT 1'
        );
        $stmt->execute([$parts[0] ?? '', $parts[1] ?? '%']);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (! $row) {
            return null;
        }
        return NewUser::firstOrCreate(
            ['email' => $row['email']],
            [
                'name' => $row['name'],
                'surname' => $row['surname'],
                'password' => '$2y$10$9X.placeholder',
                'role_on_legacy' => $row['role'],
            ]
        );
    }

    private function findOrCreateFolder(Organization $org, ?NewUser $user, string $name, bool $dry): ?NewFolder
    {
        $base = NewFolder::where('organization_id', $org->id)->where('name', $name);
        if ($user) {
            $base = $base->orWhereNull('parent_folder_id');
        }
        $existing = $base->first();
        if ($existing) {
            return $existing;
        }
        if ($dry) {
            return null;
        }
        return NewFolder::create([
            'organization_id' => $org->id,
            'name' => $name,
            'created_by' => $user?->id,
        ]);
    }
}
