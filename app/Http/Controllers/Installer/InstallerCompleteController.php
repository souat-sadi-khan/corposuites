<?php

namespace App\Http\Controllers\Installer;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class InstallerCompleteController extends Controller
{
    public function index()
    {
        $errors = [];

        // Installation environment check
        if (filter_var(env('APP_INSTALLED', false), FILTER_VALIDATE_BOOLEAN)) {
            $errors[] = 'Application is already installed.';
        }

        // Database check
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e){
            $errors[] = 'Database connection failed: ' . $e->getMessage();
        }

        // Admin check
        if (!Admin::where('email', '!=', null)->exists()) {
            $errors[] = 'No admin user found. Please create one.';
        }

        // If any error, redirect back
        if (!empty($errors)) {
            return view('installer.complete', [
                'status' => false,
                'errors' => $errors
            ]);
        }

        // Mark application as installed in environment file
        $envPath = base_path('.env');
        if (File::exists($envPath)) {
            $envContents = File::get($envPath);
            if (strpos($envContents, 'APP_INSTALLED=') !== false) {
                $envContents = preg_replace('/^APP_INSTALLED=.*$/m', 'APP_INSTALLED=TRUE', $envContents);
            } else {
                $envContents .= PHP_EOL.'APP_INSTALLED=TRUE';
            }
            File::put($envPath, $envContents);
            putenv('APP_INSTALLED=TRUE');
            $_ENV['APP_INSTALLED'] = 'TRUE';
            $_SERVER['APP_INSTALLED'] = 'TRUE';
        }

        return view('installer.complete', [
            'status' => true
        ]);
    }

}
