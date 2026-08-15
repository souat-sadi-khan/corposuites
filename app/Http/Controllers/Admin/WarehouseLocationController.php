<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WarehouseLocationRequest;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use App\Services\WarehouseLocationService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class WarehouseLocationController extends Controller
{
    use ActivityLogger;

    protected $warehouseLocationService;

    public function __construct(WarehouseLocationService $warehouseLocationService)
    {
        $this->warehouseLocationService = $warehouseLocationService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = WarehouseLocation::query()->with('warehouse');

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by warehouse
            if ($request->warehouse_id) {
                $query->where('warehouse_id', $request->warehouse_id);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%")
                      ->orWhereHas('warehouse', function ($wq) use ($search) {
                          $wq->where('name', 'like', "%{$search}%");
                      });
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.warehouse-locations.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('name', function ($row) {
                    return '<b class="tl-name-txt">' . $row->name . '</b><br><small>' . $row->code . '</small>';
                })
                ->addColumn('warehouse_name', function ($row) {
                    return $row->warehouse->name ?? '-';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.warehouse-locations.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'name', 'action'])
                ->make(true);
        }

        $warehouses = Warehouse::active()->get();

        return view('admin.warehouse-locations.index', compact('warehouses'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $warehouses = Warehouse::active()->get();

        return view('admin.warehouse-locations.create', compact('warehouses'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(WarehouseLocationRequest $request)
    {
        DB::beginTransaction();

        try {
            $warehouseLocation = $this->warehouseLocationService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'warehouse-locations',
                'action' => 'create',
                'model' => 'WarehouseLocation',
                'model_id' => $warehouseLocation->id,
                'description' => 'Warehouse Location "' . $warehouseLocation->name . '" created',
                'new_data' => $warehouseLocation->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Warehouse location created successfully.'
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
    public function edit(WarehouseLocation $warehouseLocation)
    {
        $warehouses = Warehouse::active()->get();

        return view('admin.warehouse-locations.edit', compact('warehouseLocation', 'warehouses'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(WarehouseLocationRequest $request, WarehouseLocation $warehouseLocation)
    {
        DB::beginTransaction();

        try {
            $oldData = $warehouseLocation->toArray();
            $updatedWarehouseLocation = $this->warehouseLocationService->update($warehouseLocation, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'warehouse-locations',
                'action' => 'update',
                'model' => 'WarehouseLocation',
                'model_id' => $warehouseLocation->id,
                'description' => 'Warehouse Location "' . $warehouseLocation->name . '" updated',
                'new_data' => $updatedWarehouseLocation->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.warehouse-locations.index'),
                'message' => 'Warehouse location updated successfully.'
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
    public function destroy(WarehouseLocation $warehouseLocation)
    {
        DB::beginTransaction();

        try {
            $oldData = $warehouseLocation->toArray();

            $this->warehouseLocationService->delete($warehouseLocation);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'warehouse-locations',
                'action' => 'delete',
                'model' => 'WarehouseLocation',
                'model_id' => $oldData['id'],
                'description' => 'Warehouse Location "' . $oldData['name'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Warehouse location deleted successfully.'
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

        $model = WarehouseLocation::find($id);
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
