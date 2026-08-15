<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssetCategoryRequest;
use App\Models\AssetCategory;
use App\Services\AssetCategoryService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class AssetCategoryController extends Controller
{
    use ActivityLogger;

    protected $assetCategoryService;

    public function __construct(AssetCategoryService $assetCategoryService)
    {
        $this->assetCategoryService = $assetCategoryService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = AssetCategory::query();

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by depreciation method
            if ($request->depreciation_method) {
                $query->where('depreciation_method', $request->depreciation_method);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.asset-categories.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('name', function ($row) {
                    return '<b class="tl-name-txt">' . e($row->name) . '</b><br><small>' . e($row->code) . '</small>';
                })
                ->addColumn('depreciation_badge', function ($row) {
                    $map = [
                        'none' => 'bg-secondary',
                        'straight_line' => 'bg-primary',
                        'reducing_balance' => 'bg-info',
                    ];
                    $class = $map[$row->depreciation_method] ?? 'bg-secondary';

                    return '<span class="badge ' . $class . '">' . e($row->depreciation_method_label) . '</span>';
                })
                ->addColumn('useful_life_label', function ($row) {
                    if ($row->depreciation_method === 'none') {
                        return '-';
                    }

                    return $row->useful_life_years
                        ? $row->useful_life_years . ' ' . \Illuminate\Support\Str::plural('year', $row->useful_life_years)
                        : '-';
                })
                ->addColumn('salvage_label', function ($row) {
                    return rtrim(rtrim(number_format($row->salvage_value_percent, 2), '0'), '.') . '%';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.asset-categories.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'name', 'depreciation_badge', 'action'])
                ->make(true);
        }

        return view('admin.asset-categories.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.asset-categories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AssetCategoryRequest $request)
    {
        DB::beginTransaction();

        try {
            $assetCategory = $this->assetCategoryService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'asset-categories',
                'action' => 'create',
                'model' => 'AssetCategory',
                'model_id' => $assetCategory->id,
                'description' => 'Asset Category "' . $assetCategory->name . ' (' . $assetCategory->code . ')" created',
                'new_data' => $assetCategory->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Asset category created successfully.'
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
    public function edit(AssetCategory $assetCategory)
    {
        return view('admin.asset-categories.edit', compact('assetCategory'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AssetCategoryRequest $request, AssetCategory $assetCategory)
    {
        DB::beginTransaction();

        try {
            $oldData = $assetCategory->toArray();
            $updatedAssetCategory = $this->assetCategoryService->update($assetCategory, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'asset-categories',
                'action' => 'update',
                'model' => 'AssetCategory',
                'model_id' => $assetCategory->id,
                'description' => 'Asset Category "' . $updatedAssetCategory->name . ' (' . $updatedAssetCategory->code . ')" updated',
                'new_data' => $updatedAssetCategory->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.asset-categories.index'),
                'message' => 'Asset category updated successfully.'
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
    public function destroy(AssetCategory $assetCategory)
    {
        DB::beginTransaction();

        try {
            $oldData = $assetCategory->toArray();

            $this->assetCategoryService->delete($assetCategory);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'asset-categories',
                'action' => 'delete',
                'model' => 'AssetCategory',
                'model_id' => $oldData['id'],
                'description' => 'Asset Category "' . $oldData['name'] . ' (' . $oldData['code'] . ')" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Asset category deleted successfully.'
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
     * Update status (AJAX switch toggle)
     */
    public function updateStatus(Request $request, int $id)
    {
        $request->validate([
            'status' => 'required|boolean',
        ]);

        $model = AssetCategory::find($id);
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
