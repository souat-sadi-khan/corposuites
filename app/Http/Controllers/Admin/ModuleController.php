<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Images;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ModuleRequest;
use App\Models\Module;
use App\Services\ModuleService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ModuleController extends Controller
{
    use ActivityLogger;

    protected $moduleService;

    public function __construct(ModuleService $moduleService)
    {
        $this->moduleService = $moduleService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Module::query();

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('slug', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.modules.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('installed', function ($row) {
                    return $row->installed_at ? $row->installed_at->format('d-m-Y') : '-';
                })
                ->addColumn('icon_html', function ($row) {
                    return $row->icon ? '<i class="' . $row->icon . '"></i>' : '<i class="bi-box"></i>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.modules.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'icon_html', 'action'])
                ->make(true);
        }

        return view('admin.modules.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.modules.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ModuleRequest $request)
    {
        DB::beginTransaction();

        try {
            // Create module using service
            $module = $this->moduleService->create($request->validated());


            // Log activity
            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'modules',
                'action' => 'create',
                'model' => 'Module',
                'model_id' => $module->id,
                'description' => 'Module "' . $module->name . '" created',
                'new_data' => $module->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Module created successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Module $module)
    {
        return view('admin.modules.edit', compact('module'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ModuleRequest $request, Module $module)
    {
        DB::beginTransaction();

        try {
            $oldData = $module->toArray();
            $updatedModule = $this->moduleService->update($module, $request->validated());

            // Log activity
            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'modules',
                'action' => 'update',
                'model' => 'Module',
                'model_id' => $module->id,
                'description' => 'Module "' . $module->name . '" updated',
                'new_data' => $updatedModule->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.modules.index'),
                'message' => 'Module updated successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Module $module)
    {
        DB::beginTransaction();

        try {
            $oldData = $module->toArray();

            // Delete via service
            $this->moduleService->delete($module);

            // Log activity
            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'modules',
                'action' => 'delete',
                'model' => 'Module',
                'model_id' => $module->id,
                'description' => 'Module "' . $module->name . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Module deleted successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activate module (AJAX)
     */
    public function activate(Module $module)
    {
        DB::beginTransaction();

        try {
            $oldStatus = $module->status;
            $this->moduleService->activate($module);

            // Log activity
            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'modules',
                'action' => 'activate',
                'model' => 'Module',
                'model_id' => $module->id,
                'description' => 'Module "' . $module->name . '" activated',
                'new_data' => ['status' => 'active'],
                'old_data' => ['status' => $oldStatus]
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Module activated successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deactivate module (AJAX)
     */
    public function deactivate(Module $module)
    {
        DB::beginTransaction();

        try {
            $oldStatus = $module->status;
            $this->moduleService->deactivate($module);

            // Log activity
            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'modules',
                'action' => 'deactivate',
                'model' => 'Module',
                'model_id' => $module->id,
                'description' => 'Module "' . $module->name . '" deactivated',
                'new_data' => ['status' => 'inactive'],
                'old_data' => ['status' => $oldStatus]
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Module deactivated successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Install module (AJAX)
     */
    public function install(Module $module)
    {
        DB::beginTransaction();

        try {
            $oldData = $module->toArray();
            $this->moduleService->install($module);

            // Log activity
            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'modules',
                'action' => 'install',
                'model' => 'Module',
                'model_id' => $module->id,
                'description' => 'Module "' . $module->name . '" installed',
                'new_data' => $module->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Module installed successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Uninstall module (AJAX)
     */
    public function uninstall(Module $module)
    {
        DB::beginTransaction();

        try {
            $oldData = $module->toArray();
            $this->moduleService->uninstall($module);

            // Log activity
            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'modules',
                'action' => 'uninstall',
                'model' => 'Module',
                'model_id' => $module->id,
                'description' => 'Module "' . $module->name . '" uninstalled',
                'new_data' => $module->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Module uninstalled successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateStatus(Request $request, int $id)
    {
        $request->validate([
            'status' => 'required|boolean',
        ]);

        $model = Module::find($id);
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
