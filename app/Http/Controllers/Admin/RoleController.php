<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Yajra\DataTables\Facades\DataTables;

class RoleController extends Controller
{

    use ActivityLogger;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $roles = Role::query()->withCount('permissions');
            $totalPermissions = Permission::count();

            return DataTables::of($roles)
                ->addIndexColumn()
                ->editColumn('name', function ($model) {
                    return '<b>'.$model->name.'</b><br><small>'.$model->notes.'</small>';
                })
                ->addColumn('permissions', function ($model) use ($totalPermissions) {
                    return $model->permissions_count . ' / ' . $totalPermissions;
                })
                ->editColumn('status', function($model) {
                    if($model->id == 1) {
                        return '<div class="badge-s bs-done">Default</div>';
                    } else {
                        $checked = $model->status ? 'checked' : '';
                        return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.roles.status', $model->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $model->id . '" ' . $checked . ' data-id="' . $model->id . '"></div></div>';
                    }

                })
                ->addColumn('action', function ($model) {
                    return view('admin.roles.action', compact('model'));
                })
                ->rawColumns(['name', 'permissions', 'status', 'action'])
                ->make(true);
        }

        return view('admin.roles.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.roles.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150|unique:roles,name',
            'notes' => 'nullable|string'
            // 'permissions' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ]);
        }

        $role = Role::create([
            'name'          => $request->name,
            'notes'         => $request->notes,
            'guard_name'    => 'admin'
        ]);

        // $role->givePermissionTo($request->permissions);

        $this->logActivity([
            'module' => 'role',
            'action' => 'create',
            'description' => $request->name .' role created',
            'new_data' => $role->toArray()
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Role created Successfully'
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $role = Role::with('permissions')->findOrFail($id);

        $groupedPermissions = group_permissions_by_module($role->permissions);

        return view(
            'admin.roles.show',
            compact(
                'role',
                'groupedPermissions'
            )
        );
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $role = Role::findOrFail($id);

        $permissions = Permission::all();
        $rolePermissions = $role->permissions->pluck('name')->toArray();
        return view('admin.roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    public function assign(string $id)
    {
        $role = Role::findOrFail($id);

        $permissions = Permission::all();
        $rolePermissions = $role->permissions->pluck('name')->toArray();
        return view('admin.roles.assign', compact('role', 'permissions', 'rolePermissions'));
    }

    public function assignUpdate(Request $request, string $id)
    {
        $role = Role::findOrFail($id);

        $oldPermissions = $role->permissions->pluck('name')->toArray();

        $role->syncPermissions($request->permissions ?? []);

        $role->load('permissions');

        $this->logActivity([
            'module'      => 'role',
            'action'      => 'permission',
            'description' => "Permissions updated for {$role->name}",
            'old_data'    => [
                'permissions' => $oldPermissions
            ],
            'new_data'    => [
                'permissions' => $role->permissions->pluck('name')->toArray()
            ]
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Permissions updated successfully.'
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150|unique:roles,name,' . $id,
            'notes' => 'nullable|string'
            // 'permissions' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ]);
        }

        $role = Role::with('permissions')->findOrFail($id);
        // $oldRole = $role->replicate();

        // Update role name
        $role->name = $request->name;
        $role->notes = $request->notes;
        $role->save();

        // Sync permissions
        // $role->syncPermissions($request->permissions);

        // Reload permissions to reflect changes
        // $role->load('permissions');

        // Prepare log data including permission names
        $newData = $role->toArray();
        // $newData['permissions'] = $role->permissions->pluck('name')->toArray();

        // $oldData = $oldRole->toArray();
        // $oldData['permissions'] = $oldRole->permissions->pluck('name')->toArray();

        // Log activity
        $this->logActivity([
            'module' => 'role',
            'action' => 'update',
            'description' => $request->name .' role updated',
            'new_data' => $newData,
            // 'old_data' => $oldData
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Role updated Successfully'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $role = Role::with('permissions')->findOrFail($id);

        // Prevent deleting 'admin' role
        if (strtolower($role->name) === 'Super Admin') {
            return response()->json([
                'status' => false,
                'message' => "Admin role cannot be deleted!"
            ]);
        }

        // Check if any admin has this role
        $adminsWithRole = Admin::role($role->name)->count();
        if ($adminsWithRole > 0) {
            return response()->json([
                'status' => false,
                'message' => "Cannot delete! {$adminsWithRole} admin user(s) assigned to this role."
            ]);
        }

        // Log the role before deleting
        $oldData = $role->toArray();
        $oldData['permissions'] = $role->permissions->pluck('name')->toArray();

        $this->logActivity([
            'module' => 'role',
            'action' => 'delete',
            'description' => "Role '{$role->name}' deleted",
            'old_data' => $oldData,
            'new_data' => null
        ]);

        // Delete the role
        $role->delete();

        return response()->json([
            'status' => true,
            'message' => "Role and its permissions deleted successfully"
        ]);
    }

    public function updateStatus(Request $request, int $id)
    {
        $request->validate([
            'status' => 'required|boolean',
        ]);

        $model = Role::find($id);
        if (!$model) {
            return response()->json([
                'success' => false,
                'message' => 'Record not found.'
            ]);
        }

        $model->status = $request->input('status');
        $model->save();

        return response()->json([
            'success' => true,
            'message' => 'Record status updated successfully.'
        ]);
    }
}
