<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssetLocationRequest;
use App\Models\AssetLocation;
use App\Models\Department;
use App\Services\AssetLocationService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class AssetLocationController extends Controller
{
    use ActivityLogger;

    protected $assetLocationService;

    public function __construct(AssetLocationService $assetLocationService)
    {
        $this->assetLocationService = $assetLocationService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = AssetLocation::query()->with('department')->withCount('movements');

            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            if ($request->location_type) {
                $query->where('location_type', $request->location_type);
            }

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
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.asset-locations.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('name', function ($row) {
                    return '<b class="tl-name-txt">' . e($row->name) . '</b><br><small>' . e($row->code) . '</small>';
                })
                ->addColumn('type_badge', function ($row) {
                    return '<span class="badge bg-secondary">' . ucfirst($row->location_type) . '</span>';
                })
                ->addColumn('placement', function ($row) {
                    return $row->placement ? e($row->placement) : '-';
                })
                ->addColumn('department_name', function ($row) {
                    return $row->department ? e($row->department->name) : '-';
                })
                ->addColumn('movements_label', function ($row) {
                    return $row->movements_count;
                })
                ->addColumn('action', function ($row) {
                    return view('admin.asset-locations.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'name', 'type_badge', 'action'])
                ->make(true);
        }

        return view('admin.asset-locations.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.asset-locations.create', ['departments' => $this->departments()]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AssetLocationRequest $request)
    {
        DB::beginTransaction();

        try {
            $assetLocation = $this->assetLocationService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'asset-locations',
                'action' => 'create',
                'model' => 'AssetLocation',
                'model_id' => $assetLocation->id,
                'description' => 'Asset Location "' . $assetLocation->name . ' (' . $assetLocation->code . ')" created',
                'new_data' => $assetLocation->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Asset location created successfully.'
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
    public function edit(AssetLocation $assetLocation)
    {
        return view('admin.asset-locations.edit', [
            'assetLocation' => $assetLocation,
            'departments' => $this->departments(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AssetLocationRequest $request, AssetLocation $assetLocation)
    {
        DB::beginTransaction();

        try {
            $oldData = $assetLocation->toArray();
            $updated = $this->assetLocationService->update($assetLocation, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'asset-locations',
                'action' => 'update',
                'model' => 'AssetLocation',
                'model_id' => $assetLocation->id,
                'description' => 'Asset Location "' . $updated->name . ' (' . $updated->code . ')" updated',
                'new_data' => $updated->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.asset-locations.index'),
                'message' => 'Asset location updated successfully.'
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
    public function destroy(AssetLocation $assetLocation)
    {
        DB::beginTransaction();

        try {
            $oldData = $assetLocation->toArray();

            $this->assetLocationService->delete($assetLocation);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'asset-locations',
                'action' => 'delete',
                'model' => 'AssetLocation',
                'model_id' => $oldData['id'],
                'description' => 'Asset Location "' . $oldData['name'] . ' (' . $oldData['code'] . ')" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Asset location deleted successfully.'
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

        $model = AssetLocation::find($id);
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

    protected function departments()
    {
        return Department::active()->orderBy('name')->get();
    }
}
