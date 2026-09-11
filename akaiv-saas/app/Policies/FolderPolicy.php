<?php

namespace App\Policies;

use App\Models\Folder;
use App\Models\User;

class FolderPolicy
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

    public function view(User $user, Folder $folder): bool
    {
        return $this->assertOrg($user, $folder) && (
            $user->hasPermissionTo('folder.view') ||
            $user->hasPermissionTo('folder.view_any') ||
            (int) $folder->created_by === (int) $user->id
        );
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('folder.create');
    }

    public function update(User $user, Folder $folder): bool
    {
        return $this->assertOrg($user, $folder) && (
            $user->hasPermissionTo('folder.update_any') ||
            ($user->hasPermissionTo('folder.update_own') && (int) $folder->created_by === (int) $user->id)
        );
    }

    public function delete(User $user, Folder $folder): bool
    {
        return $this->assertOrg($user, $folder) && (
            $user->hasPermissionTo('folder.delete_any') ||
            ($user->hasPermissionTo('folder.delete_own') && (int) $folder->created_by === (int) $user->id)
        );
    }

    private function assertOrg(User $user, Folder $folder): bool
    {
        $activeOrg = session('active_organization_id');

        return $activeOrg !== null && (int) $folder->organization_id === (int) $activeOrg;
    }
}
