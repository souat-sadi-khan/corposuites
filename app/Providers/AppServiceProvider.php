<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use App\Models\Lang\Language;
use App\Services\AttendanceStatusService;

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
        $this->applyConfiguredTimezone();

        View::composer('admin.layout.partials.header', function ($view) {
            $view->with(
                'languages',
                Language::where('is_active', 1)
                    ->orderByDesc('is_default')
                    ->orderBy('name')
                    ->get()
            );

            // Only ever queried for the currently authenticated admin's OWN
            // linked employee (never an arbitrary one), and only when that
            // link exists — a plain admin account costs nothing extra here.
            $employee = auth()->guard('admin')->user()?->employee;
            $view->with('attendanceWidget', $employee ? AttendanceStatusService::forEmployee($employee) : null);
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

    /**
     * The Localization settings page lets an admin pick a "Default Timezone"
     * (stored as the `timezone` system setting) but nothing ever actually
     * applied it — every now()/today() call across the whole app (including
     * every attendance check-in/check-out and "today" boundary) ran on
     * config('app.timezone') from .env (UTC) regardless of what was
     * configured here, silently disagreeing with the business's real
     * timezone. This makes the setting actually take effect, as early in
     * the request as possible, for both date_default_timezone_set() (what
     * now()/Carbon actually read) and config('app.timezone') (what anything
     * that explicitly reads that config key sees).
     *
     * Guarded so it can never break a console command on a fresh install —
     * boot() runs even for `php artisan migrate` before the settings table
     * exists yet.
     */
    private function applyConfiguredTimezone(): void
    {
        try {
            if (!Schema::hasTable('system_settings')) {
                return;
            }

            $timezone = get_settings('timezone');
            if ($timezone && in_array($timezone, timezone_identifiers_list(), true)) {
                date_default_timezone_set($timezone);
                config(['app.timezone' => $timezone]);
            }
        } catch (\Throwable $e) {
            // Never let a settings-table hiccup (e.g. mid-migration) break
            // every single request in the app.
        }
    }
}
