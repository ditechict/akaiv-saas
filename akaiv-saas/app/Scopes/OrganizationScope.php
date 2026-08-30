<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class OrganizationScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (! app()->runningInConsole() || app()->runningUnitTests()) {
            $organizationId = $this->resolveOrganizationId();
            if ($organizationId !== null) {
                $builder->where($model->qualifyColumn('organization_id'), $organizationId);
            }
        }
    }

    public function extend(Builder $builder): void
    {
        $builder->macro('withoutTenancy', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });
    }

    private function resolveOrganizationId(): ?int
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }

        if (session()->has('active_organization_id')) {
            return (int) session('active_organization_id');
        }

        $firstMembership = $user->organizations()->first();
        if ($firstMembership) {
            session()->put('active_organization_id', $firstMembership->id);
            return (int) $firstMembership->id;
        }

        return null;
    }
}
