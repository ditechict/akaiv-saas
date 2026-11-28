<?php

namespace App\Concerns;

use App\Scopes\OrganizationScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Organization;

trait BelongsToOrganization
{
    public static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope(new OrganizationScope());

        $authorizeTenantWrite = function ($model) {
            $user = auth()->user();
            if ($user && ! $user->hasRole('Platform SuperAdmin')) {
                $organization = $user->currentOrganization();
                if (! $organization) {
                    throw new \Illuminate\Auth\Access\AuthorizationException('Organization membership is required.');
                }
                if (! $model->exists && ! $model->organization_id) {
                    $model->organization_id = $organization->id;
                }
                if ((int) $model->organization_id !== (int) $organization->id ||
                    ($model->exists && (int) $model->getOriginal('organization_id') !== (int) $organization->id)) {
                    throw new \Illuminate\Auth\Access\AuthorizationException('The record belongs to another organization.');
                }
            } elseif (! $model->organization_id && session()->has('active_organization_id')) {
                $model->organization_id = session('active_organization_id');
            }
        };
        static::saving($authorizeTenantWrite);
        static::deleting($authorizeTenantWrite);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
