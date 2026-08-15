<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssetRequest;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Services\AssetService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class AssetController extends Controller
{
    use ActivityLogger;

    protected $assetService;

    public function __construct(AssetService $assetService)
    {
        $this->assetService = $assetService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Asset::query()->with('assetCategory');

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by category
            if ($request->asset_category_id) {
                $query->where('asset_category_id', $request->asset_category_id);
            }

            // Filter by lifecycle state
            if ($request->asset_status) {
                $query->where('asset_status', $request->asset_status);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('asset_code', 'like', "%{$search}%")
                        ->orWhere('serial_number', 'like', "%{$search}%")
                        ->orWhere('manufacturer', 'like', "%{$search}%");
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.assets.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('name', function ($row) {
                    return '<b class="tl-name-txt">' . e($row->name) . '</b><br><small>' . e($row->asset_code) . '</small>';
                })
                ->addColumn('category_name', function ($row) {
                    return $row->assetCategory ? e($row->assetCategory->name) : '<span class="text-danger">Uncategorised</span>';
                })
                ->addColumn('identifiers', function ($row) {
                    $serial = $row->serial_number ? 'SN: ' . e($row->serial_number) : '-';
                    $model = $row->model_number ? '<br><small>' . e($row->model_number) . '</small>' : '';

                    return $serial . $model;
                })
                ->addColumn('condition_label', function ($row) {
                    return ucfirst($row->condition);
                })
                ->addColumn('asset_status_badge', function ($row) {
                    $map = [
                        'in_store' => 'bg-secondary',
                        'in_use' => 'bg-success',
                        'under_maintenance' => 'bg-warning',
                        'disposed' => 'bg-danger',
                    ];
                    $class = $map[$row->asset_status] ?? 'bg-secondary';

                    return '<span class="badge ' . $class . '">' . e($row->asset_status_label) . '</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.assets.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'name', 'category_name', 'identifiers', 'asset_status_badge', 'action'])
                ->make(true);
        }

        $assetCategories = AssetCategory::active()->orderBy('name')->get();

        return view('admin.assets.index', compact('assetCategories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $assetCategories = AssetCategory::active()->orderBy('name')->get();

        return view('admin.assets.create', compact('assetCategories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AssetRequest $request)
    {
        DB::beginTransaction();

        try {
            $asset = $this->assetService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'assets',
                'action' => 'create',
                'model' => 'Asset',
                'model_id' => $asset->id,
                'description' => 'Asset "' . $asset->name . ' (' . $asset->asset_code . ')" registered',
                'new_data' => $asset->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Asset registered successfully.'
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
    public function edit(Asset $asset)
    {
        $assetCategories = AssetCategory::active()->orderBy('name')->get();

        return view('admin.assets.edit', compact('asset', 'assetCategories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AssetRequest $request, Asset $asset)
    {
        DB::beginTransaction();

        try {
            $oldData = $asset->toArray();
            $updatedAsset = $this->assetService->update($asset, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'assets',
                'action' => 'update',
                'model' => 'Asset',
                'model_id' => $asset->id,
                'description' => 'Asset "' . $updatedAsset->name . ' (' . $updatedAsset->asset_code . ')" updated',
                'new_data' => $updatedAsset->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.assets.index'),
                'message' => 'Asset updated successfully.'
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
    public function destroy(Asset $asset)
    {
        DB::beginTransaction();

        try {
            $oldData = $asset->toArray();

            $this->assetService->delete($asset);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'assets',
                'action' => 'delete',
                'model' => 'Asset',
                'model_id' => $oldData['id'],
                'description' => 'Asset "' . $oldData['name'] . ' (' . $oldData['asset_code'] . ')" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Asset deleted successfully.'
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

        $model = Asset::find($id);
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
