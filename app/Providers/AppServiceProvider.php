<?php

namespace App\Providers;

use App\Models\App;
use App\Models\Link;
use App\Policies\AppPolicy;
use App\Policies\LinkPolicy;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // Register authorization policies
        Gate::policy(App::class, AppPolicy::class);
        Gate::policy(Link::class, LinkPolicy::class);

        // Register layout components
        Blade::component('layouts.app', 'layouts.app');
        Blade::component('layouts.auth', 'layouts.auth');
    }
}
