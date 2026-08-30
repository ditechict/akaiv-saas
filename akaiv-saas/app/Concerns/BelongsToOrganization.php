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

        static::creating(function ($model) {
            if (! $model->organization_id && session()->has('active_organization_id')) {
                $model->organization_id = session('active_organization_id');
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
