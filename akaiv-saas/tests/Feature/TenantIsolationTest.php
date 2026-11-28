<?php

use App\Models\Document;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    Queue::fake();
});

function tenantOrg(string $slug): Organization
{
    return Organization::create([
        'name' => ucfirst($slug),
        'slug' => $slug,
        'contact_email' => $slug.'@example.test',
    ]);
}

function tenantDoc(Organization $organization, string $name): Document
{
    return Document::create([
        'organization_id' => $organization->id,
        'friendly_name' => $name,
        'original_filename' => $name.'.pdf',
        'slug' => $name,
        'storage_disk' => 'private',
        'storage_path' => 'documents/'.$name.'.pdf',
        'status' => 'published',
    ]);
}

it('scopes queries to the active organization', function (): void {
    $orgA = tenantOrg('org-a');
    $orgB = tenantOrg('org-b');
    $docA = tenantDoc($orgA, 'alpha');
    $docB = tenantDoc($orgB, 'beta');

    $user = User::create(['name' => 'A User', 'email' => 'a@example.test', 'password' => 'password']);
    $user->organizations()->attach($orgA->id, ['role' => 'member']);

    $this->actingAs($user);
    session(['active_organization_id' => $orgA->id]);

    $visible = Document::query()->pluck('id')->all();

    expect($visible)->toContain($docA->id)
        ->and($visible)->not->toContain($docB->id);
});

it('can bypass tenancy with the withoutTenancy macro', function (): void {
    $orgA = tenantOrg('macro-a');
    $orgB = tenantOrg('macro-b');
    tenantDoc($orgA, 'macro-alpha');
    tenantDoc($orgB, 'macro-beta');

    $user = User::create(['name' => 'Macro User', 'email' => 'macro@example.test', 'password' => 'password']);
    $user->organizations()->attach($orgA->id, ['role' => 'member']);

    $this->actingAs($user);
    session(['active_organization_id' => $orgA->id]);

    expect(Document::query()->count())->toBe(1)
        ->and(Document::withoutTenancy()->count())->toBe(2);
});

it('auto-sets organization_id on create from the active organization', function (): void {
    $org = tenantOrg('auto-org');
    $user = User::create(['name' => 'Auto User', 'email' => 'auto@example.test', 'password' => 'password']);
    $user->organizations()->attach($org->id, ['role' => 'member']);

    $this->actingAs($user);
    session(['active_organization_id' => $org->id]);

    $folder = \App\Models\Folder::create(['name' => 'Filings']);

    expect((int) $folder->organization_id)->toBe($org->id);
});

it('denies tenant document queries for a user with no organization membership', function (): void {
    $org = tenantOrg('unrelated-org');
    tenantDoc($org, 'unrelated-document');
    $user = User::create(['name' => 'No Membership', 'email' => 'no-membership@example.test', 'password' => 'password']);

    $this->actingAs($user);
    session()->forget('active_organization_id');

    expect(Document::query()->count())->toBe(0);
});

it('denies a session-selected organization outside the user memberships', function (): void {
    $orgA = tenantOrg('authorized-org');
    $orgB = tenantOrg('unauthorized-org');
    tenantDoc($orgA, 'authorized-document');
    $docB = tenantDoc($orgB, 'unauthorized-document');
    $user = User::create(['name' => 'Restricted User', 'email' => 'restricted@example.test', 'password' => 'password']);
    $user->organizations()->attach($orgA->id, ['role' => 'member']);

    $this->actingAs($user);
    session(['active_organization_id' => $orgB->id]);

    expect(Document::query()->pluck('id')->all())->not->toContain($docB->id);
});

it('rejects a foreign organization on create and preserves the database', function (): void {
    $orgA = tenantOrg('write-a');
    $orgB = tenantOrg('write-b');
    $user = User::create(['name' => 'Writer', 'email' => 'writer@example.test', 'password' => 'password']);
    $user->organizations()->attach($orgA->id, ['role' => 'member']);
    $this->actingAs($user);
    session(['active_organization_id' => $orgB->id]);

    expect(fn () => tenantDoc($orgB, 'foreign-create'))
        ->toThrow(\Illuminate\Auth\Access\AuthorizationException::class);
    expect(Document::withoutTenancy()->count())->toBe(0);
    expect((int) session('active_organization_id'))->toBe($orgA->id);
});

it('rejects an update to a foreign record loaded before authentication', function (): void {
    $orgA = tenantOrg('update-a');
    $orgB = tenantOrg('update-b');
    $document = tenantDoc($orgB, 'foreign-update');
    $user = User::create(['name' => 'Updater', 'email' => 'updater@example.test', 'password' => 'password']);
    $user->organizations()->attach($orgA->id, ['role' => 'member']);
    $this->actingAs($user);
    session(['active_organization_id' => $orgA->id]);

    expect(fn () => $document->update(['friendly_name' => 'Changed']))
        ->toThrow(\Illuminate\Auth\Access\AuthorizationException::class);
    expect(Document::withoutTenancy()->find($document->id)->friendly_name)->toBe('foreign-update');
});

it('denies creation after membership removal and clears the stale selection', function (): void {
    $org = tenantOrg('revoked-write');
    $user = User::create(['name' => 'Revoked', 'email' => 'revoked@example.test', 'password' => 'password']);
    $user->organizations()->attach($org->id, ['role' => 'member']);
    $this->actingAs($user);
    session(['active_organization_id' => $org->id]);
    $user->organizations()->detach($org->id);

    expect(fn () => \App\Models\Folder::create(['name' => 'Forbidden']))
        ->toThrow(\Illuminate\Auth\Access\AuthorizationException::class);
    expect(session()->has('active_organization_id'))->toBeFalse();
});

it('denies policy access to a foreign record despite a forged selection', function (): void {
    \Spatie\Permission\Models\Permission::create(['name' => 'document.view_any', 'guard_name' => 'web']);
    \Spatie\Permission\Models\Permission::create(['name' => 'document.view', 'guard_name' => 'web']);
    \Spatie\Permission\Models\Permission::create(['name' => 'document.update_any', 'guard_name' => 'web']);
    $orgA = tenantOrg('policy-a');
    $orgB = tenantOrg('policy-b');
    $document = tenantDoc($orgB, 'policy-foreign');
    $user = User::create(['name' => 'Policy User', 'email' => 'policy-user@example.test', 'password' => 'password']);
    $user->organizations()->attach($orgA->id, ['role' => 'member']);
    $user->givePermissionTo(['document.view_any', 'document.update_any']);
    $this->actingAs($user);
    session(['active_organization_id' => $orgB->id]);

    expect($user->can('view', $document))->toBeFalse()
        ->and($user->can('update', $document))->toBeFalse();
});

it('rejects signed document access across tenants and after membership removal', function (string $route): void {
    \Spatie\Permission\Models\Permission::create(['name' => 'document.view', 'guard_name' => 'web']);
    $orgA = tenantOrg('http-a');
    $orgB = tenantOrg('http-b');
    $document = tenantDoc($orgB, 'http-foreign');
    $user = User::create(['name' => 'HTTP User', 'email' => 'http-user@example.test', 'password' => 'password']);
    $user->organizations()->attach($orgA->id, ['role' => 'member']);
    $this->actingAs($user);
    $url = \Illuminate\Support\Facades\URL::temporarySignedRoute($route, now()->addMinute(), ['document' => $document->uuid]);
    $this->withSession(['active_organization_id' => $orgB->id])->get($url)->assertNotFound();

    $user->organizations()->detach();
    $this->withSession(['active_organization_id' => $orgB->id])->get($url)->assertNotFound();
})->with(['documents.preview', 'documents.download']);

it('rejects moving an existing record to another tenant and deleting a foreign record', function (): void {
    $orgA = tenantOrg('move-a');
    $orgB = tenantOrg('move-b');
    $own = tenantDoc($orgA, 'move-own');
    $foreign = tenantDoc($orgB, 'delete-foreign');
    $user = User::create(['name' => 'Mover', 'email' => 'mover@example.test', 'password' => 'password']);
    $user->organizations()->attach($orgA->id, ['role' => 'member']);
    $this->actingAs($user);
    session(['active_organization_id' => $orgA->id]);

    expect(fn () => $own->update(['organization_id' => $orgB->id]))
        ->toThrow(\Illuminate\Auth\Access\AuthorizationException::class);
    expect(fn () => $foreign->delete())
        ->toThrow(\Illuminate\Auth\Access\AuthorizationException::class);
    expect(Document::withoutTenancy()->find($own->id)->organization_id)->toBe($orgA->id)
        ->and(Document::withoutTenancy()->find($foreign->id))->not->toBeNull();
});
