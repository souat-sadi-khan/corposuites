<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssetMaintenanceRecordRequest;
use App\Models\Asset;
use App\Models\AssetMaintenanceRecord;
use App\Models\AssetMaintenanceSchedule;
use App\Models\Employee;
use App\Models\Vendor;
use App\Services\AssetMaintenanceRecordService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class AssetMaintenanceRecordController extends Controller
{
    use ActivityLogger;

    protected $recordService;

    public function __construct(AssetMaintenanceRecordService $recordService)
    {
        $this->recordService = $recordService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = AssetMaintenanceRecord::query()->with(['asset', 'schedule', 'vendor', 'performedBy']);

            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            if ($request->record_status) {
                $query->where('record_status', $request->record_status);
            }

            if ($request->maintenance_type) {
                $query->where('maintenance_type', $request->maintenance_type);
            }

            if ($request->asset_id) {
                $query->where('asset_id', $request->asset_id);
            }

            // Planned work came from a schedule; unplanned did not.
            if ($request->origin === 'planned') {
                $query->whereNotNull('asset_maintenance_schedule_id');
            } elseif ($request->origin === 'unplanned') {
                $query->whereNull('asset_maintenance_schedule_id');
            }

            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhereHas('asset', function ($sub) use ($search) {
                            $sub->where('name', 'like', "%{$search}%")
                                ->orWhere('asset_code', 'like', "%{$search}%");
                        });
                });
            }

            // Most recent work first — this is a log, so recency wins.
            $query->orderBy('performed_date', 'DESC')->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.asset-maintenance-records.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('title_col', function ($row) {
                    $asset = $row->asset
                        ? e($row->asset->asset_code) . ' · ' . e($row->asset->name)
                        : '<span class="text-danger">Asset removed</span>';

                    return '<b class="tl-name-txt">' . e($row->title) . '</b><br><small>' . $asset . '</small>';
                })
                ->addColumn('origin', function ($row) {
                    return $row->schedule
                        ? '<span class="badge bg-info">Planned</span><br><small>' . e($row->schedule->title) . '</small>'
                        : '<span class="badge bg-secondary">Unplanned</span>';
                })
                ->addColumn('type_label', function ($row) {
                    return ucfirst($row->maintenance_type);
                })
                ->addColumn('performed_date_formatted', function ($row) {
                    return $row->performed_date->format('d M, Y');
                })
                ->addColumn('performed_by_col', function ($row) {
                    $parts = [];
                    if ($row->performedBy) {
                        $parts[] = e(trim($row->performedBy->first_name . ' ' . $row->performedBy->last_name));
                    }
                    if ($row->vendor) {
                        $parts[] = '<small>' . e($row->vendor->name) . '</small>';
                    }

                    return $parts ? implode('<br>', $parts) : '-';
                })
                ->addColumn('cost_col', function ($row) {
                    $cost = $row->cost !== null ? number_format($row->cost, 2) : '-';

                    if ($row->downtime_hours !== null && (float) $row->downtime_hours > 0) {
                        $hours = rtrim(rtrim(number_format($row->downtime_hours, 2), '0'), '.');

                        return $cost . '<br><small>' . $hours . ' hr downtime</small>';
                    }

                    return $cost;
                })
                ->addColumn('record_status_badge', function ($row) {
                    $map = [
                        'in_progress' => 'bg-warning',
                        'completed' => 'bg-success',
                        'cancelled' => 'bg-danger',
                    ];
                    $class = $map[$row->record_status] ?? 'bg-secondary';

                    return '<span class="badge ' . $class . '">' . e($row->status_label) . '</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.asset-maintenance-records.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'title_col', 'origin', 'performed_by_col', 'cost_col', 'record_status_badge', 'action'])
                ->make(true);
        }

        return view('admin.asset-maintenance-records.index', [
            'assets' => Asset::active()->orderBy('asset_code')->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.asset-maintenance-records.create', $this->formData());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AssetMaintenanceRecordRequest $request)
    {
        DB::beginTransaction();

        try {
            $record = $this->recordService->create($request->validated());
            $record->load('asset');

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'asset-maintenance-records',
                'action' => 'create',
                'model' => 'AssetMaintenanceRecord',
                'model_id' => $record->id,
                'description' => 'Maintenance "' . $record->title . '" recorded for asset "' . ($record->asset->asset_code ?? $record->asset_id) . '"',
                'new_data' => $record->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Maintenance record saved successfully.'
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
    public function edit(AssetMaintenanceRecord $assetMaintenanceRecord)
    {
        return view('admin.asset-maintenance-records.edit', array_merge(
            $this->formData(),
            ['record' => $assetMaintenanceRecord]
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AssetMaintenanceRecordRequest $request, AssetMaintenanceRecord $assetMaintenanceRecord)
    {
        DB::beginTransaction();

        try {
            $oldData = $assetMaintenanceRecord->toArray();
            $updated = $this->recordService->update($assetMaintenanceRecord, $request->validated());
            $updated->load('asset');

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'asset-maintenance-records',
                'action' => 'update',
                'model' => 'AssetMaintenanceRecord',
                'model_id' => $assetMaintenanceRecord->id,
                'description' => 'Maintenance record "' . $updated->title . '" updated',
                'new_data' => $updated->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.asset-maintenance-records.index'),
                'message' => 'Maintenance record updated successfully.'
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
    public function destroy(AssetMaintenanceRecord $assetMaintenanceRecord)
    {
        DB::beginTransaction();

        try {
            $oldData = $assetMaintenanceRecord->toArray();

            $this->recordService->delete($assetMaintenanceRecord);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'asset-maintenance-records',
                'action' => 'delete',
                'model' => 'AssetMaintenanceRecord',
                'model_id' => $oldData['id'],
                'description' => 'Maintenance record "' . $oldData['title'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Maintenance record deleted successfully.'
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

        $model = AssetMaintenanceRecord::find($id);
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
     * Dropdown data shared by create/edit. Schedules carry their asset id
     * as a data attribute so the form can narrow them to the selected
     * asset — the Form Request enforces the same rule server-side.
     */
    protected function formData(): array
    {
        return [
            'assets' => Asset::active()->orderBy('asset_code')->get(),
            'schedules' => AssetMaintenanceSchedule::active()->with('asset')->orderBy('title')->get(),
            'vendors' => Vendor::active()->orderBy('name')->get(),
            'employees' => Employee::active()->orderBy('first_name')->get(),
        ];
    }
}
