<?php

use App\Models\Document;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    Permission::create(['name' => 'document.view', 'guard_name' => 'web']);
    Queue::fake();
});

function agentContext(): array
{
    $organization = Organization::create([
        'name' => 'Agent Court',
        'slug' => 'agent-court-'.uniqid(),
        'contact_email' => 'agent@example.test',
    ]);
    $user = User::create(['name' => 'Agent User', 'email' => 'agent-'.uniqid().'@example.test', 'password' => 'password']);
    $user->organizations()->attach($organization->id, ['role' => 'member']);
    $document = Document::create([
        'organization_id' => $organization->id,
        'owner_id' => $user->id,
        'friendly_name' => 'Judgment',
        'original_filename' => 'judgment.pdf',
        'slug' => 'judgment-'.uniqid(),
        'storage_disk' => 'private',
        'storage_path' => 'documents/judgment.pdf',
        'status' => 'published',
        'virus_scanned' => true,
    ]);

    return [$organization, $user, $document];
}

it('rejects unauthenticated analysis requests', function (): void {
    [, , $document] = agentContext();

    $this->postJson('/documents/'.$document->uuid.'/analyze')->assertUnauthorized();
});

it('returns a bad gateway when the worker fails', function (): void {
    [$organization, $user, $document] = agentContext();
    session(['active_organization_id' => $organization->id]);

    config([
        'services.document_agent.url' => 'https://worker.example.test',
        'services.document_agent.secret' => 'secret',
    ]);

    Http::fake(['*' => Http::response(['error' => 'boom'], 500)]);

    $this->actingAs($user)
        ->postJson('/documents/'.$document->uuid.'/analyze')
        ->assertStatus(502);
});

it('returns the worker analysis on success', function (): void {
    [$organization, $user, $document] = agentContext();
    session(['active_organization_id' => $organization->id]);

    config([
        'services.document_agent.url' => 'https://worker.example.test',
        'services.document_agent.secret' => 'secret',
    ]);

    Http::fake(['*' => Http::response(['summary' => 'A short judgment.'], 200)]);

    $this->actingAs($user)
        ->postJson('/documents/'.$document->uuid.'/analyze')
        ->assertOk()
        ->assertJson(['summary' => 'A short judgment.']);
});

it('errors when the worker is not configured', function (): void {
    [$organization, $user, $document] = agentContext();
    session(['active_organization_id' => $organization->id]);

    config([
        'services.document_agent.url' => null,
        'services.document_agent.secret' => null,
    ]);

    $this->withoutExceptionHandling();
    $this->expectException(RuntimeException::class);

    $this->actingAs($user)->postJson('/documents/'.$document->uuid.'/analyze');
});
