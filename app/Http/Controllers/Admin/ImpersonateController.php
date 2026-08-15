<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Traits\ActivityLogger;
use Illuminate\Support\Facades\Auth;

class ImpersonateController extends Controller
{
    use ActivityLogger;

    public function start(string $adminId)
    {
        $currentAdmin = auth('admin')->user();
        $admin = Admin::findOrFail($adminId);

        // abort_if(!$currentAdmin->can('users.impersonate'), 403);
        abort_if($admin->id == $currentAdmin->id, 403);

        session()->put(
            'impersonator_id',
            $currentAdmin->id
        );

        session()->put(
            'impersonating',
            true
        );

        $this->logActivity([
            'module'        =>  'authentication',
            'action'        =>  'impersonate_start',
            'model'         =>  Admin::class,
            'model_id'      =>  $admin->id,
            'description'   =>  "Started impersonating {$admin->name}",
            'meta'          =>  [
                'original_admin_id' =>  $currentAdmin->id,
                'target_admin_id'   =>  $admin->id,
                'target_name'       =>  $admin->name,
                'started_at'        =>  now()
            ]
        ]);

        Auth::guard('admin')->login($admin);

        return redirect()->route('admin.dashboard');
    }

    public function stop()
    {
        $currentAdmin = auth('admin')->user();
        $originalAdminId = session('impersonator_id');

        abort_if(!$originalAdminId, 403);

        $originalAdmin = Admin::findOrFail($originalAdminId);

        $this->logActivity([
            'module'        =>  'authentication',
            'action'        =>  'impersonate_stop',
            'model'         =>  Admin::class,
            'model_id'      =>  $currentAdmin->id,
            'description'   =>  "Stopped impersonating {$currentAdmin->name}",
            'meta'          =>  [
                'original_admin_id' =>  $originalAdminId,
                'target_admin_id'   =>  $currentAdmin->id,
                'ended_at'          =>  now()
            ]
        ]);

        Auth::guard('admin')->login(
            $originalAdmin
        );

        session()->forget([
            'impersonator_id',
            'impersonating'
        ]);

        return redirect()->route('admin.dashboard');
    }
}
