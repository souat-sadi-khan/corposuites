<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ModuleMenuRequest;
use App\Models\Module;
use App\Models\ModuleMenu;
use App\Services\ModuleMenuService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ModuleMenuController extends Controller
{
    use ActivityLogger;

    protected $menuService;

    public function __construct(ModuleMenuService $menuService)
    {
        $this->menuService = $menuService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = ModuleMenu::with(['module', 'parent']);

            // Filter by module
            if ($request->module_id) {
                $query->where('module_id', $request->module_id);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('label', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%")
                      ->orWhere('route', 'like', "%{$search}%")
                      ->orWhere('permission', 'like', "%{$search}%");
                });
            }

            // Status filter
            if ($request->status !== null && $request->status !== '') {
                $query->where('status', $request->status);
            }

            $query->orderBy('module_id')->orderBy('order');

            return DataTables::eloquent($query)
                ->addColumn('module_name', function ($row) {
                    return $row->module ? $row->module->name : '-';
                })
                ->addColumn('parent_label', function ($row) {
                    return $row->parent ? $row->parent->label : '-';
                })
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.module-menus.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.module-menus.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'action'])
                ->make(true);
        }

        // For non-AJAX, load modules list for filter
        $modules = Module::orderBy('name')->get();
        $moduleId = $request->get('module_id');
        return view('admin.module-menus.index', compact('modules', 'moduleId'));
    }

    /**
     * Show the form for creating a new resource (via modal/offcanvas).
     */
    public function create(Request $request)
    {
        // This can be used to load a create form via AJAX (if using modal)
        $modules = Module::orderBy('name')->get();
        $moduleId = $request->get('module_id');
        return view('admin.module-menus.create', compact('modules', 'moduleId'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ModuleMenuRequest $request)
    {
        DB::beginTransaction();

        try {
            $menu = $this->menuService->create($request->validated());

            // Log activity
            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth('admin')->id(),
                'module' => 'module_menus',
                'action' => 'create',
                'model' => 'ModuleMenu',
                'model_id' => $menu->id,
                'description' => 'Menu "' . $menu->label . '" created for module ' . ($menu->module->name ?? ''),
                'new_data' => $menu->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Menu created successfully.'
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
    public function edit(ModuleMenu $moduleMenu)
    {
        // For AJAX modal loading
        $modules = Module::orderBy('name')->get();
        $model = $moduleMenu;
        return view('admin.module-menus.edit', compact('modules', 'model'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ModuleMenuRequest $request, ModuleMenu $moduleMenu)
    {
        DB::beginTransaction();

        try {
            $oldData = $moduleMenu->toArray();
            $updatedMenu = $this->menuService->update($moduleMenu, $request->validated());

            // Log activity
            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth('admin')->id(),
                'module' => 'module_menus',
                'action' => 'update',
                'model' => 'ModuleMenu',
                'model_id' => $moduleMenu->id,
                'description' => 'Menu "' . $moduleMenu->label . '" updated',
                'new_data' => $updatedMenu->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.module-menus.index', ['module_id' => $moduleMenu->module_id]),
                'message' => 'Menu updated successfully.'
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
    public function destroy(ModuleMenu $moduleMenu)
    {
        DB::beginTransaction();

        try {
            $oldData = $moduleMenu->toArray();
            $moduleId = $moduleMenu->module_id;

            $this->menuService->delete($moduleMenu);

            // Log activity
            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth('admin')->id(),
                'module' => 'module_menus',
                'action' => 'delete',
                'model' => 'ModuleMenu',
                'model_id' => $moduleMenu->id,
                'description' => 'Menu "' . $moduleMenu->label . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Menu deleted successfully.'
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
     * Toggle menu status (AJAX)
     */
    public function toggleStatus(ModuleMenu $moduleMenu)
    {
        DB::beginTransaction();

        try {
            $oldStatus = $moduleMenu->status;
            $this->menuService->toggleStatus($moduleMenu);

            // Log activity
            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth('admin')->id(),
                'module' => 'module_menus',
                'action' => 'toggle_status',
                'model' => 'ModuleMenu',
                'model_id' => $moduleMenu->id,
                'description' => 'Menu "' . $moduleMenu->label . '" status toggled to ' . ($moduleMenu->status ? 'Active' : 'Inactive'),
                'new_data' => ['status' => $moduleMenu->status],
                'old_data' => ['status' => $oldStatus]
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Status updated.',
                'new_status' => $moduleMenu->status
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
     * Reorder menus (AJAX)
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:module_menus,id'
        ]);

        DB::beginTransaction();

        try {
            $this->menuService->reorder($request->input('order'));

            // Log activity (optional)
            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth('admin')->id(),
                'module' => 'module_menus',
                'action' => 'reorder',
                'model' => 'ModuleMenu',
                'model_id' => null,
                'description' => 'Menus reordered',
                'new_data' => ['order' => $request->input('order')],
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Menus reordered successfully.'
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
     * Get menus by module (for parent dropdown, AJAX)
     */
    public function getByModule(Request $request)
    {
        $moduleId = $request->get('module_id');
        $menus = ModuleMenu::where('module_id', $moduleId)
            ->orderBy('order')
            ->get(['id', 'label', 'parent_id']);
        return response()->json($menus);
    }

    public function updateStatus(Request $request, int $id)
    {
        $request->validate([
            'status' => 'required|boolean',
        ]);

        $model = ModuleMenu::find($id);
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
