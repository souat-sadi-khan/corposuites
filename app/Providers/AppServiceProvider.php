<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use App\Models\Lang\Language;

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
        View::composer('admin.layout.partials.header', function ($view) {
            $view->with(
                'languages',
                Language::where('is_active', 1)
                    ->orderByDesc('is_default')
                    ->orderBy('name')
                    ->get()
            );
        });

        /*
         * Super Admin always passes every permission/ability check,
         * regardless of whether that exact permission row has been
         * created/synced yet. This is a safety net around permission
         * enforcement (route `permission:` middleware, @can() in
         * Blade, and the sidebar's own can() checks) so a missed or
         * misspelled permission slug can never lock the Super Admin
         * role itself out of its own panel.
         */
        Gate::before(function ($user, string $ability) {
            return $user && method_exists($user, 'hasRole') && $user->hasRole('Super Admin')
                ? true
                : null;
        });
    }
}
