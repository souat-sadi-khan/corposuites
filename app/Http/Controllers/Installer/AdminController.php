<?php

namespace App\Http\Controllers\Installer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin;
use App\Models\AdminProfile;
use Illuminate\Support\Facades\Hash;
use App\Traits\ActivityLogger;

class AdminController extends Controller
{
    use ActivityLogger;

    public function index()
    {
        return view('installer.step4');
    }

    public function store(Request $request)
    {
        // Validation
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed'
        ]);

        try {
            $admin = Admin::create([
                'name' => $request->name,
                'email' => $request->email,
                'status' => 1,
                'password' => Hash::make($request->password)
            ]);

            AdminProfile::create([
                'admin_id' => $admin->id
            ]);

            $admin->assignRole('Super Admin');

            $this->logActivity([
                'module' => 'installer',
                'action' => 'create',
                'model' => 'Admin',
                'model_id' => $admin->id,
                'description' => 'New Admin User created',
                'new_data' => $admin->toArray()
            ]);

        } catch(\Exception $e) {
            return response()->json([
                'status'=>false,
                'message'=>'Failed to create admin: '.$e->getMessage()
            ]);
        }

        return response()->json([
            'status'=>true,
            'message'=>'Super Admin created successfully.'
        ]);
    }

}
