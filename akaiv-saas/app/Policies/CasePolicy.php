<?php

namespace App\Policies;

use App\Models\CaseFile;
use App\Models\User;

class CasePolicy
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

    public function view(User $user, CaseFile $case): bool
    {
        return $this->assertOrg($user, $case) && (
            $user->hasPermissionTo('case.view') ||
            $user->hasPermissionTo('case.view_any') ||
            (int) $case->created_by === (int) $user->id
        );
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('case.create');
    }

    public function update(User $user, CaseFile $case): bool
    {
        return $this->assertOrg($user, $case) && (
            $user->hasPermissionTo('case.update_any') ||
            ($user->hasPermissionTo('case.update_own') && (int) $case->created_by === (int) $user->id)
        );
    }

    public function delete(User $user, CaseFile $case): bool
    {
        return $this->assertOrg($user, $case) && (
            $user->hasPermissionTo('case.delete_any') ||
            ($user->hasPermissionTo('case.delete_own') && (int) $case->created_by === (int) $user->id)
        );
    }

    private function assertOrg(User $user, CaseFile $case): bool
    {
        $activeOrg = session('active_organization_id');

        return $activeOrg !== null && (int) $case->organization_id === (int) $activeOrg;
    }
}
