<?php

namespace App\Policies;

use App\Models\Share;
use App\Models\User;

class SharePolicy
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

    public function view(User $user, Share $share): bool
    {
        return $this->assertOrg($user, $share) && (
            $user->hasPermissionTo('share.view_any') ||
            (int) $share->shared_by === (int) $user->id
        );
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('share.create');
    }

    public function update(User $user, Share $share): bool
    {
        return $this->assertOrg($user, $share) && (
            $user->hasPermissionTo('share.update_any') ||
            (int) $share->shared_by === (int) $user->id
        );
    }

    public function delete(User $user, Share $share): bool
    {
        return $this->assertOrg($user, $share) && (
            $user->hasPermissionTo('share.delete_any') ||
            (int) $share->shared_by === (int) $user->id
        );
    }

    private function assertOrg(User $user, Share $share): bool
    {
        $activeOrg = session('active_organization_id');
        $document = $share->document;

        if ($activeOrg === null || $document === null) {
            return false;
        }

        return (int) $document->organization_id === (int) $activeOrg;
    }
}
