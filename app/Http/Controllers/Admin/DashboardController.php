<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use App\Models\ActivityLog;
use Carbon\Carbon;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->guard('admin')->user();

        $totalStaff = Admin::count();
        $activeStaff = Admin::where('status', 1)->count();
        $inactiveStaff = Admin::where('status', 0)->count();
        $totalRoles = Role::count();

        $activities = ActivityLog::with('admin')
            ->latest()
            ->take(7)
            ->get();

        $notifications = Notification::latest()
            ->take(6)
            ->get();

        $unreadNotifications = Notification::where('is_read', 0)->count();

        $labels = [];
        $activityData = [];
        $loginData = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);

            $labels[] = $date->format('M d');

            $activityData[] = ActivityLog::whereDate('created_at', $date)->count();

            $loginData[] = ActivityLog::where('module', 'auth')
                ->where('action', 'login')
                ->whereDate('created_at', $date)
                ->count();
        }

        $todayActivity = ActivityLog::whereDate('created_at', today())->count();

        $thisWeek = ActivityLog::whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ])->count();

        $lastWeek = ActivityLog::whereBetween('created_at', [
            now()->copy()->subWeek()->startOfWeek(),
            now()->copy()->subWeek()->endOfWeek()
        ])->count();

        $thisMonth = ActivityLog::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $lastMonth = ActivityLog::whereMonth('created_at', now()->copy()->subMonth()->month)
            ->whereYear('created_at', now()->copy()->subMonth()->year)
            ->count();

        $weekGrowth = $lastWeek ? round((($thisWeek - $lastWeek) / $lastWeek) * 100, 1) : 100;
        $monthGrowth = $lastMonth ? round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1) : 100;

        $appVersion = config('app.version', '1.0.0');

        $systemInfo = [
            'app_version'     => $appVersion,
            'laravel'         => app()->version(),
            'php'             => PHP_VERSION,
            'database'        => DB::select('select version() as version')[0]->version ?? 'Unknown',
            'server'          => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'environment'     => ucfirst(app()->environment()),
            'timezone'        => config('app.timezone'),
            'memory_limit'    => ini_get('memory_limit'),
            'upload_limit'    => ini_get('upload_max_filesize'),
            'execution_time'  => ini_get('max_execution_time').'s',
            'session_driver'  => config('session.driver'),
            'cache_driver'    => config('cache.default'),
            'queue_driver'    => config('queue.default'),
            'mail_driver'     => config('mail.default'),
        ];

        $serverHealth = [
            ['Database', 'Healthy', 'healthy'],
            ['Storage', round((disk_total_space('/') - disk_free_space('/')) / disk_total_space('/') * 100).' % Free', 'healthy'],
            ['Mail', 'Healthy', 'healthy'],
            ['Cache', 'Healthy', 'healthy'],
            ['Queue', ucfirst(config('queue.default')), 'healthy'],
            ['Scheduler', 'Healthy', 'healthy'],
            ['Cron', 'On Time', 'healthy'],
        ];

        return view('admin.dashboard', compact(
            'user',
            'totalStaff',
            'activeStaff',
            'inactiveStaff',
            'totalRoles',
            'labels',
            'activityData',
            'loginData',
            'todayActivity',
            'weekGrowth',
            'monthGrowth',
            'activities',
            'notifications',
            'unreadNotifications',
            'systemInfo',
            'serverHealth',
        ));
    }
}
