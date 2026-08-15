<?php

namespace App\Http\Controllers\Installer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SystemSetting;
use App\Traits\ActivityLogger;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

class CompanyController extends Controller
{
    use ActivityLogger;

    public function index()
    {
        return view('installer.step5');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'system_name' => 'required|string|max:150',
            'system_email' => 'required|email',

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

            $logoName = time().'_'.$request->system_logo->getClientOriginalName();

            $request->system_logo->move(public_path('uploads/system'), $logoName);

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

            $favicon = time().'_'.$request->system_favicon->getClientOriginalName();

            $request->system_favicon->move(public_path('uploads/system'), $favicon);

            SystemSetting::updateOrCreate(
                ['key' => 'system_favicon'],
                [
                    'value' => $favicon,
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
            'module' => 'installer',
            'action' => 'create',
            'description' => 'System company configuration completed',
            'new_data' => $settings
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Company information saved successfully.'
        ]);
    }
}
