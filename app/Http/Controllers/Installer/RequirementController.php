<?php

namespace App\Http\Controllers\Installer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RequirementController extends Controller
{
    public function index()
    {
        $requirements = [];

        // PHP Version
        $requirements['php'] = [
            'name' => 'PHP Version >= 8.1',
            'current' => PHP_VERSION,
            'status' => version_compare(PHP_VERSION, '8.1.0', '>=')
        ];

        // Required Extensions
        $requiredExtensions = ['openssl','pdo','mbstring','curl','fileinfo','json','tokenizer','xml','ctype'];
        foreach($requiredExtensions as $ext){
            $requirements['extensions'][$ext] = extension_loaded($ext);
        }

        // Disabled Functions
        $disabled = ini_get('disable_functions');
        $requirements['disabled_functions'] = !empty($disabled) ? explode(',', str_replace(' ', '', $disabled)) : [];

        // File Permissions
        $paths = [
            storage_path() => 'Storage folder',
            base_path('bootstrap/cache') => 'Bootstrap cache folder'
        ];
        foreach($paths as $path => $desc){
            $requirements['permissions'][$desc] = is_writable($path);
        }

        // PHP Recommended Settings
        $requirements['settings'] = [
            'memory_limit' => ini_get('memory_limit') >= '128M',
            'max_execution_time' => ini_get('max_execution_time') >= 60,
            // 'post_max_size' => ini_get('post_max_size') >= '8M',
            // 'upload_max_filesize' => ini_get('upload_max_filesize') >= '8M',
            'allow_url_fopen' => ini_get('allow_url_fopen') == '1' || strtolower(ini_get('allow_url_fopen')) == 'on'
        ];

        return view('installer.step1', compact('requirements'));
    }
}
