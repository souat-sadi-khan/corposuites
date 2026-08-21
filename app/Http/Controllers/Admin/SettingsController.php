<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Helpers\Images;
use App\Models\ActivityLog;
use App\Models\AdminProfile;
use Illuminate\Http\Request;
use App\Models\SystemSetting;
use App\Traits\ActivityLogger;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class SettingsController extends Controller
{
    use ActivityLogger;

    public function index()
    {
        return view('admin.settings.index');
    }

    public function branding()
    {
        return view('admin.settings.branding');
    }

    public function company()
    {
        return view('admin.settings.company');
    }

    public function appearance()
    {
        return view('admin.settings.appearance');
    }

    public function localization()
    {
        return view('admin.settings.localization');
    }

    // Drop this method into your existing admin controller (wherever
    // the `admin.settings.sitemap` route is currently wired to).

    public function sitemap()
    {
        $routes = collect(Route::getRoutes())
            ->filter(function ($route) {
                return str_starts_with(
                    $route->uri(),
                    'admin'
                );
            })
            ->map(function ($route) {

                $methods = collect($route->methods())
                    ->reject(fn ($method) => $method === 'HEAD')
                    ->values()
                    ->toArray();

                return [
                    'name'    => $route->getName() ?? 'Unnamed Route',
                    'uri'     => '/' . ltrim($route->uri(), '/'),
                    'methods' => $methods,
                    'action'  => $route->getActionName(),
                    'segment' => explode('/', trim($route->uri(), '/')),
                ];
            })
            ->values();

        $tree = $this->buildRouteTree($routes);

        return view(
            'admin.settings.sitemap',
            compact('tree')
        );
    }

    public function optimize()
    {
        try {

            Artisan::call('optimize:clear');

            Artisan::call('config:clear');

            Artisan::call('cache:clear');

            Artisan::call('route:clear');

            Artisan::call('view:clear');

            return response()->json([
                'status' => true,
                'message' => 'System optimized successfully.'
            ]);


        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ],500);

        }
    }


    /**
     * Build a nested tree from flat route segments, attaching each
     * route to its terminal node in the SAME pass that creates the
     * node — no second walk, so there's nothing to attach to the
     * wrong level.
     */
    private function buildRouteTree($routes)
    {
        $tree = [];

        foreach ($routes as $route) {
            $segments = $route['segment'];
            $current  = &$tree;
            $lastKey  = count($segments) - 1;

            foreach ($segments as $i => $segment) {
                if (!isset($current[$segment])) {
                    $current[$segment] = [
                        '_route'    => null,
                        '_children' => [],
                    ];
                }

                if ($i === $lastKey) {
                    // Terminal segment — attach the route here.
                    $current[$segment]['_route'] = $route;
                } else {
                    // Not the last segment — descend into children
                    // for the NEXT iteration.
                    $current = &$current[$segment]['_children'];
                }
            }

            unset($current); // break the reference before the next route
        }

        return $tree;
    }

    public function releaseHistory()
    {
        return view('admin.settings.release-history');
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [

            // 'system_name' => 'required|string|max:150',
            // 'system_email' => 'required|email',

            'system_logo' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2000',
            'system_favicon' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:1000',

        ]);

        if ($validator->fails()) {

            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ]);
        }

        $settings = $request->except('_token','system_logo','system_favicon');

        foreach ($settings as $key => $value) {

            SystemSetting::updateOrCreate(

                ['key' => $key],

                [
                    'value' => $value,
                    'group' => 'general'
                ]

            );
        }

        /*
        |--------------------------------------------------------------------------
        | Upload Logo
        |--------------------------------------------------------------------------
        */

        if ($request->hasFile('system_logo')) {
            $logoName = Images::upload('system', $request->system_logo);

            SystemSetting::updateOrCreate(
                ['key' => 'system_logo'],
                [
                    'value' => $logoName,
                    'group' => 'branding'
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Upload Favicon
        |--------------------------------------------------------------------------
        */

        if ($request->hasFile('system_favicon')) {
            $favicon = Images::upload('system', $request->system_favicon);

            SystemSetting::updateOrCreate(
                ['key' => 'system_favicon'],
                [
                    'value' => $favicon,
                    'group' => 'branding'
                ]
            );
        }

        if ($request->hasFile('brand_dark_logo')) {
            $brand_dark_logo = Images::upload('system', $request->brand_dark_logo);

            SystemSetting::updateOrCreate(
                ['key' => 'brand_dark_logo'],
                [
                    'value' => $brand_dark_logo,
                    'group' => 'branding'
                ]
            );
        }

        if ($request->hasFile('app_icon')) {
            $app_icon = Images::upload('system', $request->app_icon);

            SystemSetting::updateOrCreate(
                ['key' => 'app_icon'],
                [
                    'value' => $app_icon,
                    'group' => 'branding'
                ]
            );
        }

        if ($request->hasFile('email_logo')) {
            $email_logo = Images::upload('system', $request->email_logo);

            SystemSetting::updateOrCreate(
                ['key' => 'email_logo'],
                [
                    'value' => $email_logo,
                    'group' => 'branding'
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Clear Settings Cache
        |--------------------------------------------------------------------------
        */

        Cache::forget('system_settings');

        /*
        |--------------------------------------------------------------------------
        | Activity Log
        |--------------------------------------------------------------------------
        */

        $this->logActivity([
            'module' => 'settings',
            'action' => 'update',
            'description' => 'System configuration updated',
            'new_data' => $settings
        ]);

        return response()->json([
            'status' => true,
            'load' => true,
            'message' => 'Information updated successfully.'
        ]);
    }

    public function myProfile()
    {
        $skills = null;

        $adminId = Auth::guard('admin')->user()->id;

        $profile = AdminProfile::where('admin_id', $adminId)->first();

        if ($profile && $profile->skills) {
            $skills = json_decode($profile->skills);
        }

        $admin = Auth::guard('admin')->user();
        
        $activities = ActivityLog::where('actor_type', 'user')
            ->where('actor_id', $adminId)
            ->whereIn('action', ['create','update','delete','login'])
            ->latest()
            ->limit(15)
            ->get();

        return view('admin.settings.profile', compact('profile', 'admin', 'skills','activities'));
    }

    public function editProfile()
    {
        $skills = null;
        $profile = AdminProfile::where('admin_id', Auth::guard('admin')->user()->id)->first();

        if($profile->skills) {
            $skills = implode(',', json_decode($profile->skills));
        }

        return view('admin.settings.edit-profile', compact('profile', 'skills'));
    }

    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'name' => 'required|string|max:150',
            'email' => 'required|email|unique:admins,email,'. Auth::guard('admin')->user()->id,
            'username' => 'nullable|string|max:25|unique:admins,username,'. Auth::guard('admin')->user()->id,

            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2000',
            // 'cover_picture' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:1000',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ]);
        }

        $admin = Auth::guard('admin')->user();
        $oldAdmin = $admin;
        $admin->name = $request->name;
        $admin->username = $request->username;
        $admin->email = $request->email;
        $admin->phone = $request->phone;

        if($request->has('avatar')) {
            $admin->avatar = Images::upload('admin', $request->avatar);
        }

        $admin->save();

        $this->logActivity([
            'module' => 'admin',
            'action' => 'update',
            'description' => 'Admin info updated',
            'old_data' => $oldAdmin->toArray(),
            'new_data' => $admin->toArray()
        ]);

        $skills = [];
        if($request->skills) {
            $skills = json_encode(explode(',', $request->skills));
        }

        $profile = AdminProfile::where('admin_id', $admin->id)->first();
        $oldProfile = $profile;
        if($profile) {
            $profile->designation = $request->designation;
            $profile->address = $request->address;
            $profile->city = $request->city;
            $profile->postal_code = $request->postal_code;
            $profile->state = $request->state;
            $profile->country = $request->country;
            $profile->whatsapp = $request->whatsapp;
            $profile->highest_education = $request->highest_education;
            $profile->university = $request->university;
            $profile->major = $request->major;
            $profile->current_job_title = $request->current_job_title;
            $profile->current_company = $request->current_company;
            $profile->years_of_experience = $request->years_of_experience;
            $profile->facebook_url = $request->facebook_url;
            $profile->twitter_url = $request->twitter_url;
            $profile->instagram_url = $request->instagram_url;
            $profile->linkedin_url = $request->linkedin_url;
            $profile->youtube_url = $request->youtube_url;
            $profile->pinterest_url = $request->pinterest_url;
            $profile->tiktok_url = $request->tiktok_url;
            $profile->github_url = $request->github_url;
            $profile->website_url = $request->website_url;
            $profile->cover_photo = $request->cover_theme;
            $profile->skills = $skills;
            if($request->has('cover_photo')) {
                $profile->cover_photo = Images::upload('admin', $request->cover_photo);
            }
            $profile->save();
        }

        $this->logActivity([
            'module' => 'profile',
            'action' => 'update',
            'description' => 'Admin profile updated',
            'old_data' => $oldProfile->toArray(),
            'new_data' => $profile->toArray()
        ]);

        return response()->json([
            'status' => true,
            'goto' => route('admin.profile'),
            'message' => 'Information updated successfully.'
        ]);
    }

    public function editPassword()
    {
        return view('admin.settings.password');
    }

    public function updatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'old_password' => 'required|string|min:8|max:150',
            'password' => 'required|string|min:8|max:150|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ]);
        }

        $admin = Auth::guard('admin')->user();

        if (!Hash::check($request->old_password, $admin->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Old password is incorrect'
            ]);
        }

        // update password
        $admin->password = Hash::make($request->password);
        $admin->save();

        // activity log
        $this->logActivity([
            'module' => 'profile',
            'action' => 'update',
            'description' => 'Admin changed account password',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Password changed successfully'
        ]);
    }

    public function showActivity($id)
    {
        $activity = ActivityLog::findOrFail($id);

        return view('admin.settings.activity', compact('activity'));
    }
}
