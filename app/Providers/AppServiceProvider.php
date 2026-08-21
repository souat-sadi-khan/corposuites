<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;
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
    }
}
