<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DocumentPolicy
{
    public function before(User $user, string $ability): bool|null
    {
        if ($user->hasRole('Platform SuperAdmin')) {
            return true;
        }
        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->currentOrganization() !== null;
    }

    public function view(User $user, Document $document): bool
    {
        return $this->assertOrg($user, $document) && (
            $user->hasPermissionTo('document.view') ||
            $document->owner_id === $user->id ||
            $user->hasPermissionTo('document.view_any')
        );
    }

    public function download(User $user, Document $document): bool
    {
        return $this->view($user, $document) && $user->hasPermissionTo('document.download');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('document.create');
    }

    public function update(User $user, Document $document): bool
    {
        return $this->assertOrg($user, $document) && (
            $user->hasPermissionTo('document.update_any') ||
            ($user->hasPermissionTo('document.update_own') && $document->owner_id === $user->id)
        );
    }

    public function delete(User $user, Document $document): bool
    {
        return $this->assertOrg($user, $document) && (
            $user->hasPermissionTo('document.delete_any') ||
            ($user->hasPermissionTo('document.delete_own') && $document->owner_id === $user->id)
        );
    }

    private function assertOrg(User $user, Document $document): bool
    {
        $activeOrg = session('active_organization_id');
        return $activeOrg !== null && (int) $document->organization_id === (int) $activeOrg;
    }
}
