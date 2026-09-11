<?php

namespace App\Providers;

use App\Models\CaseFile;
use App\Models\Document;
use App\Models\Folder;
use App\Models\Organization;
use App\Models\Share;
use App\Policies\CasePolicy;
use App\Policies\DocumentPolicy;
use App\Policies\FolderPolicy;
use App\Policies\OrganizationPolicy;
use App\Policies\SharePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Document::class => DocumentPolicy::class,
        Folder::class => FolderPolicy::class,
        CaseFile::class => CasePolicy::class,
        Share::class => SharePolicy::class,
        Organization::class => OrganizationPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
