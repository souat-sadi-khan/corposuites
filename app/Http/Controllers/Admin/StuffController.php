<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Images;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin;
use App\Models\AdminProfile;
use App\Traits\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class StuffController extends Controller
{

    use ActivityLogger;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Admin::query()
                ->with(['roles'])
                ->leftJoin(
                    'admin_profiles',
                    'admins.id',
                    '=',
                    'admin_profiles.admin_id'
                )
                ->select([
                    'admins.id',
                    'admins.name',
                    'admins.avatar',
                    'admins.email',
                    'admins.phone',
                    'admins.status',
                    'admins.created_at',

                    'admin_profiles.designation',
                    'admin_profiles.current_company',
                    'admin_profiles.city',
                    'admin_profiles.state',
                    'admin_profiles.country',
                ]);


            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn(
                    'admins.status',
                    $statuses
                );
            }


            if ($request->search) {
                $search = $request->search;
                $query->where(function($q) use($search){
                    $q->where(
                        'admins.name',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'admins.email',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'admin_profiles.designation',
                        'like',
                        "%{$search}%"
                    );
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('avatar_url',function($row){
                    return $row->avatar
                        ? asset($row->avatar)
                        : asset(
                            'assets/system/images/default-avatar.png'
                        );
                })
                ->addColumn('position',function($row){
                    return $row->designation ?? '-';
                })
                ->addColumn('department',function($row){
                    return $row->roles
                        ->pluck('name')
                        ->implode(', ') ?: '-';
                })
                ->addColumn('location',function($row){
                    return collect([
                        $row->city,
                        $row->state,
                        $row->country
                    ])
                    ->filter()
                    ->implode(', ') ?: '-';
                })
                ->addColumn('hr_year',function($row){
                    return $row->created_at
                        ->diffInYears(now())
                        . ' Years';

                })
                ->editColumn('status',function($row){
                    if($row->id == 1) {
                        return '<div class="badge-s bs-done">Super Admin</div>';
                    } else {
                        $checked = $row->status ? 'checked' : '';
                        return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.stuff.status', $row->id) . '" class="switch form-control-sm form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                    }
                })
                ->addColumn('action',function($row){
                    return view(
                        'admin.stuff.action',
                        compact('row')
                    )->render();

                })
                ->rawColumns([
                    'status',
                    'action'
                ])
                ->make(true);
        }

        return view('admin.stuff.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $roles = Role::all();

        return view('admin.stuff.create', compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validation
        $validator = Validator::make($request->all(), [
            'role_id' => 'required|exists:roles,id',
            'name' => 'required|string|max:150',
            // 'username' => 'required|string|max:100|unique:admins,username',
            'email' => 'required|email|max:150|unique:admins,email',
            'password' => 'required|min:8|string|max:20',
            'phone' => 'nullable|string|max:20',
            'designation' => 'nullable|string|max:100',
            'whatsapp' => 'nullable|string|max:20',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'cover_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:4096',
            'skills' => 'nullable|string',
            'highest_education' => 'nullable|string|max:150',
            'university' => 'nullable|string|max:150',
            'major' => 'nullable|string|max:150',
            'current_job_title' => 'nullable|string|max:150',
            'current_company' => 'nullable|string|max:150',
            'years_of_experience' => 'nullable|integer|min:0|max:100',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'facebook_url' => 'nullable|url',
            'twitter_url' => 'nullable|url',
            'instagram_url' => 'nullable|url',
            'linkedin_url' => 'nullable|url',
            'pinterest_url' => 'nullable|url',
            'tiktok_url' => 'nullable|url',
            'github_url' => 'nullable|url',
            'website_url' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ]);
        }

        DB::beginTransaction();

        // Create Admin
        $admin = new Admin();
        $admin->name = $request->name;
        $admin->username = $request->email;
        $admin->email = $request->email;
        $admin->phone = $request->phone;
        $admin->password = Hash::make($request->password);
        if ($request->hasFile('avatar')) {
            $admin->avatar = Images::upload('admin', $request->avatar);
        }
        $admin->save();

        $skills = null;
        if($request->skills) {
            $skills = json_encode(explode(',', $request->skills));
        }

        $adminProfile = new AdminProfile();
        $adminProfile->admin_id = $admin->id;
        $adminProfile->designation = $request->designation;
        $adminProfile->whatsapp = $request->whatsapp;
        $adminProfile->skills = $skills;
        $adminProfile->highest_education = $request->highest_education;
        $adminProfile->university = $request->university;
        $adminProfile->major = $request->major;
        $adminProfile->current_job_title = $request->current_job_title;
        $adminProfile->current_company = $request->current_company;
        $adminProfile->years_of_experience = $request->years_of_experience;
        $adminProfile->address = $request->address;
        $adminProfile->city = $request->city;
        $adminProfile->state = $request->state;
        $adminProfile->postal_code = $request->postal_code;
        $adminProfile->country = $request->country;
        $adminProfile->facebook_url = $request->facebook_url;
        $adminProfile->twitter_url = $request->twitter_url;
        $adminProfile->instagram_url = $request->instagram_url;
        $adminProfile->linkedin_url = $request->linkedin_url;
        $adminProfile->pinterest_url = $request->pinterest_url;
        $adminProfile->tiktok_url = $request->tiktok_url;
        $adminProfile->github_url = $request->github_url;
        $adminProfile->website_url = $request->website_url;
        if ($request->hasFile('cover_photo')) {
            $adminProfile->cover_photo = Images::upload('admin', $request->cover_photo);
        }
        $adminProfile->save();

        // Assign Role
        $role = Role::findOrFail($request->role_id);
        $admin->assignRole($role);

        // Activity log
        $this->logActivity([
            'actor_type' => 'admin',
            'actor_id' => auth('admin')->id(),
            'module' => 'admin',
            'action' => 'create',
            'model' => 'Admin',
            'model_id' => $admin->id,
            'description' => 'Admin ' . $admin->name . ' created',
            'new_data' => $admin->toArray(),
            'old_data' => null
        ]);

        DB::commit();

        // Return JSON
        return response()->json([
            'status' => true,
            'goto' => route('admin.stuff.index'),
            'message' => 'Admin updated successfully',
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $model = Admin::with('profile')->findOrFail($id);
        $roles = Role::all();
        $skills = null;

        if($model->profile->skills) {
            $skills = implode(',', json_decode($model->profile->skills));
        }
        return view('admin.stuff.edit', compact('roles', 'skills', 'model'));
    }

    /**
     * Show the form for editing the specified password.
     */
    public function editPassword(string $id)
    {
        $model = Admin::findOrFail($id);
        return view('admin.stuff.edit-password', compact('model'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Validation
        $validator = Validator::make($request->all(), [
            'role_id' => 'required|exists:roles,id',
            'name' => 'required|string|max:150',
            // 'username' => 'required|string|max:100|unique:admins,username,'. $id,
            'email' => 'required|email|max:150|unique:admins,email,'. $id,
            'phone' => 'nullable|string|max:20',
            'designation' => 'nullable|string|max:100',
            'whatsapp' => 'nullable|string|max:20',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'cover_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:4096',
            'skills' => 'nullable|string',
            'highest_education' => 'nullable|string|max:150',
            'university' => 'nullable|string|max:150',
            'major' => 'nullable|string|max:150',
            'current_job_title' => 'nullable|string|max:150',
            'current_company' => 'nullable|string|max:150',
            'years_of_experience' => 'nullable|integer|min:0|max:100',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'facebook_url' => 'nullable|url',
            'twitter_url' => 'nullable|url',
            'instagram_url' => 'nullable|url',
            'linkedin_url' => 'nullable|url',
            'pinterest_url' => 'nullable|url',
            'tiktok_url' => 'nullable|url',
            'github_url' => 'nullable|url',
            'website_url' => 'nullable|url',
            'status' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ]);
        }

        DB::beginTransaction();

        // Create Admin
        $admin = Admin::findOrFail($id);
        $oldAdmin = $admin;
        $admin->name = $request->name;
        $admin->email = $request->email;
        $admin->phone = $request->phone ?? $admin->phone;
        $admin->status = $request->status ?? $admin->status;
        if ($request->hasFile('avatar')) {
            $admin->avatar = Images::upload('admin', $request->avatar);
        }
        $admin->save();

        $skills = null;
        if($request->skills) {
            $skills = json_encode(explode(',', $request->skills));
        }

        $adminProfile = AdminProfile::where('admin_id', $admin->id)->first();
        $oldAdmin->profile = $adminProfile;
        $adminProfile->admin_id = $admin->id;
        $adminProfile->designation = $request->designation ?? $adminProfile->designation;
        $adminProfile->whatsapp = $request->whatsapp ?? $adminProfile->whatsapp;
        $adminProfile->skills = $skills;
        $adminProfile->highest_education = $request->highest_education ?? $adminProfile->highest_education;
        $adminProfile->university = $request->university;
        $adminProfile->major = $request->major;
        $adminProfile->current_job_title = $request->current_job_title;
        $adminProfile->current_company = $request->current_company;
        $adminProfile->years_of_experience = $request->years_of_experience;
        $adminProfile->address = $request->address;
        $adminProfile->city = $request->city;
        $adminProfile->state = $request->state;
        $adminProfile->postal_code = $request->postal_code;
        $adminProfile->country = $request->country;
        $adminProfile->facebook_url = $request->facebook_url;
        $adminProfile->twitter_url = $request->twitter_url;
        $adminProfile->instagram_url = $request->instagram_url;
        $adminProfile->linkedin_url = $request->linkedin_url;
        $adminProfile->pinterest_url = $request->pinterest_url;
        $adminProfile->tiktok_url = $request->tiktok_url;
        $adminProfile->github_url = $request->github_url;
        $adminProfile->website_url = $request->website_url;
        if ($request->hasFile('cover_photo')) {
            $adminProfile->cover_photo = Images::upload('admin', $request->cover_photo);
        }
        $adminProfile->save();


        // Assign Role
        $role = Role::findOrFail($request->role_id);
        $admin->syncRoles([$role->name]);

        // Activity log
        $this->logActivity([
            'actor_type' => 'admin',
            'actor_id' => auth('admin')->id(),
            'module' => 'admin',
            'action' => 'update',
            'model' => 'Admin',
            'model_id' => $admin->id,
            'description' => 'Admin ' . $admin->name . ' updated',
            'new_data' => $admin->toArray(),
            'old_data' => $oldAdmin->toArray()
        ]);

        DB::commit();

        // Return JSON
        return response()->json([
            'status' => true,
            'goto' => route('admin.stuff.index'),
            'message' => 'Admin information updated successfully',
        ]);
    }

    public function updatePassword(Request $request, string $id)
    {
        // Validation
        $validator = Validator::make($request->all(), [
            'password' => 'required|min:8|max:20|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ]);
        }

        $admin = Admin::findOrFail($id);
        $admin->password = Hash::make($request->password);
        $admin->save();

        // Activity log
        $this->logActivity([
            'actor_type' => 'admin',
            'actor_id' => auth('admin')->id(),
            'module' => 'admin',
            'action' => 'update',
            'model' => 'Admin',
            'model_id' => $admin->id,
            'description' => 'Admin ' . $admin->name . ' password updated',
        ]);

        // Return JSON
        return response()->json([
            'status' => true,
            'goto' => route('admin.stuff.index'),
            'message' => 'Admin password updated successfully',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $admin = Admin::with('profile')->findOrFail($id);

        if ($admin->id === 1) {
            return response()->json([
                'status' => false,
                'message' => "Admin cannot be deleted!"
            ]);
        }

        $oldData = $admin->toArray();
        $oldData['permissions'] = $admin->profile->toArray();

        $this->logActivity([
            'module' => 'admin',
            'action' => 'delete',
            'description' => "Admin '{$admin->name}' deleted",
            'old_data' => $oldData,
            'new_data' => null
        ]);

        $admin->delete();

        return response()->json([
            'status' => true,
            'message' => "Admin deleted successfully"
        ]);
    }

    public function updateStatus(Request $request, int $id)
    {
        $request->validate([
            'status' => 'required|boolean',
        ]);

        $model = Admin::find($id);
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
