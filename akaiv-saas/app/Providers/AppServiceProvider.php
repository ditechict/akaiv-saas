<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Database\Eloquent\Model;
use App\Models\Document;
use App\Observers\DocumentObserver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        Model::unguard();

        Gate::before(function ($user, $ability) {
            if ($user->hasRole('Platform SuperAdmin')) {
                return true;
            }
            return null;
        });

        if (class_exists(Document::class) && class_exists(DocumentObserver::class)) {
            Document::observe(DocumentObserver::class);
        }
    }
}
