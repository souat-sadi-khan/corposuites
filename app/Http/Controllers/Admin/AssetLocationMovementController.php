<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssetLocationMovementRequest;
use App\Models\Asset;
use App\Models\AssetLocation;
use App\Models\AssetLocationMovement;
use App\Services\AssetLocationMovementService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class AssetLocationMovementController extends Controller
{
    use ActivityLogger;

    protected $movementService;

    public function __construct(AssetLocationMovementService $movementService)
    {
        $this->movementService = $movementService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = AssetLocationMovement::query()->with(['asset', 'assetLocation', 'movedBy']);

            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            if ($request->asset_location_id) {
                $query->where('asset_location_id', $request->asset_location_id);
            }

            if ($request->asset_id) {
                $query->where('asset_id', $request->asset_id);
            }

            // "Current location only" keeps just the latest movement per
            // asset. Which row that is depends on the ordering of the other
            // rows, so it is expressed as a correlated subquery rather than
            // a stored `is_current` flag (there is none — see the model).
            if ($request->current_only) {
                $query->whereIn('id', function ($sub) {
                    $sub->selectRaw('MAX(id)')
                        ->from('asset_location_movements as m')
                        ->whereRaw('m.asset_id = asset_location_movements.asset_id')
                        ->whereRaw('m.moved_date = (select max(m2.moved_date) from asset_location_movements as m2 where m2.asset_id = asset_location_movements.asset_id)');
                });
            }

            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->whereHas('asset', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%")
                            ->orWhere('asset_code', 'like', "%{$search}%");
                    })->orWhereHas('assetLocation', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
                });
            }

            $query->orderBy('moved_date', 'DESC')->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.asset-location-movements.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('asset_name', function ($row) {
                    if (! $row->asset) {
                        return '<span class="text-danger">Asset removed</span>';
                    }

                    return '<b class="tl-name-txt">' . e($row->asset->name) . '</b><br><small>' . e($row->asset->asset_code) . '</small>';
                })
                ->addColumn('location_name', function ($row) {
                    if (! $row->assetLocation) {
                        return '-';
                    }

                    $placement = $row->assetLocation->placement;

                    return e($row->assetLocation->name)
                        . '<br><small>' . e($row->assetLocation->code) . ($placement ? ' · ' . e($placement) : '') . '</small>';
                })
                ->addColumn('moved_date_formatted', function ($row) {
                    return $row->moved_date->format('d M, Y');
                })
                ->addColumn('current_badge', function ($row) {
                    return $row->is_current
                        ? '<span class="badge bg-success">Current</span>'
                        : '<span class="badge bg-secondary">History</span>';
                })
                ->addColumn('moved_by_name', function ($row) {
                    return $row->movedBy ? e($row->movedBy->name) : '-';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.asset-location-movements.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'asset_name', 'location_name', 'current_badge', 'action'])
                ->make(true);
        }

        return view('admin.asset-location-movements.index', [
            'assetLocations' => AssetLocation::active()->orderBy('name')->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.asset-location-movements.create', $this->formData());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AssetLocationMovementRequest $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->validated();
            $data['moved_by'] = auth()->guard('admin')->id();

            $movement = $this->movementService->create($data);
            $movement->load('asset', 'assetLocation');

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'asset-location-movements',
                'action' => 'create',
                'model' => 'AssetLocationMovement',
                'model_id' => $movement->id,
                'description' => 'Asset "' . ($movement->asset->asset_code ?? $movement->asset_id) . '" moved to "' . ($movement->assetLocation->name ?? $movement->asset_location_id) . '"',
                'new_data' => $movement->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Asset movement recorded successfully.'
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
    public function edit(AssetLocationMovement $assetLocationMovement)
    {
        return view('admin.asset-location-movements.edit', array_merge(
            $this->formData(),
            ['assetLocationMovement' => $assetLocationMovement]
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AssetLocationMovementRequest $request, AssetLocationMovement $assetLocationMovement)
    {
        DB::beginTransaction();

        try {
            $oldData = $assetLocationMovement->toArray();
            $updated = $this->movementService->update($assetLocationMovement, $request->validated());
            $updated->load('asset', 'assetLocation');

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'asset-location-movements',
                'action' => 'update',
                'model' => 'AssetLocationMovement',
                'model_id' => $assetLocationMovement->id,
                'description' => 'Movement updated for asset "' . ($updated->asset->asset_code ?? $updated->asset_id) . '"',
                'new_data' => $updated->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.asset-location-movements.index'),
                'message' => 'Asset movement updated successfully.'
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
    public function destroy(AssetLocationMovement $assetLocationMovement)
    {
        DB::beginTransaction();

        try {
            $oldData = $assetLocationMovement->toArray();

            $this->movementService->delete($assetLocationMovement);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'asset-location-movements',
                'action' => 'delete',
                'model' => 'AssetLocationMovement',
                'model_id' => $oldData['id'],
                'description' => 'Movement deleted for asset id ' . $oldData['asset_id'],
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Asset movement deleted successfully.'
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

        $model = AssetLocationMovement::find($id);
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

    /**
     * Dropdown data shared by create/edit. Every non-disposed asset is
     * offered — unlike Asset Assignment there is nothing to exclude, since
     * an asset can be moved any number of times; the Form Request only
     * blocks re-recording the location it is already in.
     */
    protected function formData(): array
    {
        return [
            'assets' => Asset::active()->where('asset_status', '!=', 'disposed')->orderBy('asset_code')->get(),
            'assetLocations' => AssetLocation::active()->orderBy('name')->get(),
        ];
    }
}
