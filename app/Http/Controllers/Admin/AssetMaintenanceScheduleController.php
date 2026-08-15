<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssetMaintenanceScheduleRequest;
use App\Models\Asset;
use App\Models\AssetMaintenanceSchedule;
use App\Models\Employee;
use App\Models\Vendor;
use App\Services\AssetMaintenanceScheduleService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class AssetMaintenanceScheduleController extends Controller
{
    use ActivityLogger;

    protected $scheduleService;

    public function __construct(AssetMaintenanceScheduleService $scheduleService)
    {
        $this->scheduleService = $scheduleService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = AssetMaintenanceSchedule::query()->with(['asset', 'vendor', 'assignedTo']);

            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            if ($request->schedule_status) {
                $query->where('schedule_status', $request->schedule_status);
            }

            if ($request->maintenance_type) {
                $query->where('maintenance_type', $request->maintenance_type);
            }

            // Due state is computed from next_due_date, not a stored flag,
            // so it is expressed here as a date comparison on schedules
            // that are still active.
            if ($request->due === 'overdue') {
                $query->where('schedule_status', 'active')
                    ->whereNotNull('next_due_date')
                    ->whereDate('next_due_date', '<', now()->toDateString());
            } elseif ($request->due === 'due_soon') {
                $query->where('schedule_status', 'active')
                    ->whereNotNull('next_due_date')
                    ->whereDate('next_due_date', '>=', now()->toDateString())
                    ->whereDate('next_due_date', '<=', now()->addDays(30)->toDateString());
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

            // Soonest-due first, so what needs doing next sits at the top.
            $query->orderByRaw('next_due_date IS NULL')
                ->orderBy('next_due_date', 'ASC')
                ->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.asset-maintenance-schedules.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('title_col', function ($row) {
                    $asset = $row->asset
                        ? e($row->asset->asset_code) . ' · ' . e($row->asset->name)
                        : '<span class="text-danger">Asset removed</span>';

                    return '<b class="tl-name-txt">' . e($row->title) . '</b><br><small>' . $asset . '</small>';
                })
                ->addColumn('type_label', function ($row) {
                    return ucfirst($row->maintenance_type);
                })
                ->addColumn('frequency_label_col', function ($row) {
                    return $row->frequency_label;
                })
                ->addColumn('due_col', function ($row) {
                    if (! $row->next_due_date) {
                        return '<span class="text-muted">-</span>';
                    }

                    $date = $row->next_due_date->format('d M, Y');

                    if ($row->is_overdue) {
                        return '<span class="text-danger">' . $date . '</span><br><small class="text-danger">Overdue by ' . abs($row->days_until_due) . ' day(s)</small>';
                    }

                    if ($row->is_due) {
                        return '<span class="text-danger">' . $date . '</span><br><small class="text-danger">Due today</small>';
                    }

                    return $date . '<br><small>in ' . $row->days_until_due . ' day(s)</small>';
                })
                ->addColumn('responsible', function ($row) {
                    $parts = [];
                    if ($row->assignedTo) {
                        $parts[] = e(trim($row->assignedTo->first_name . ' ' . $row->assignedTo->last_name));
                    }
                    if ($row->vendor) {
                        $parts[] = '<small>' . e($row->vendor->name) . '</small>';
                    }

                    return $parts ? implode('<br>', $parts) : '-';
                })
                ->addColumn('schedule_status_badge', function ($row) {
                    $map = [
                        'active' => 'bg-success',
                        'paused' => 'bg-warning',
                        'completed' => 'bg-secondary',
                        'cancelled' => 'bg-danger',
                    ];
                    $class = $map[$row->schedule_status] ?? 'bg-secondary';

                    return '<span class="badge ' . $class . '">' . ucfirst($row->schedule_status) . '</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.asset-maintenance-schedules.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'title_col', 'due_col', 'responsible', 'schedule_status_badge', 'action'])
                ->make(true);
        }

        return view('admin.asset-maintenance-schedules.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.asset-maintenance-schedules.create', $this->formData());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AssetMaintenanceScheduleRequest $request)
    {
        DB::beginTransaction();

        try {
            $schedule = $this->scheduleService->create($request->validated());
            $schedule->load('asset');

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'asset-maintenance-schedules',
                'action' => 'create',
                'model' => 'AssetMaintenanceSchedule',
                'model_id' => $schedule->id,
                'description' => 'Maintenance schedule "' . $schedule->title . '" created for asset "' . ($schedule->asset->asset_code ?? $schedule->asset_id) . '"',
                'new_data' => $schedule->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Maintenance schedule created successfully.'
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
    public function edit(AssetMaintenanceSchedule $assetMaintenanceSchedule)
    {
        return view('admin.asset-maintenance-schedules.edit', array_merge(
            $this->formData(),
            ['schedule' => $assetMaintenanceSchedule]
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AssetMaintenanceScheduleRequest $request, AssetMaintenanceSchedule $assetMaintenanceSchedule)
    {
        DB::beginTransaction();

        try {
            $oldData = $assetMaintenanceSchedule->toArray();
            $updated = $this->scheduleService->update($assetMaintenanceSchedule, $request->validated());
            $updated->load('asset');

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'asset-maintenance-schedules',
                'action' => 'update',
                'model' => 'AssetMaintenanceSchedule',
                'model_id' => $assetMaintenanceSchedule->id,
                'description' => 'Maintenance schedule "' . $updated->title . '" updated',
                'new_data' => $updated->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.asset-maintenance-schedules.index'),
                'message' => 'Maintenance schedule updated successfully.'
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
    public function destroy(AssetMaintenanceSchedule $assetMaintenanceSchedule)
    {
        DB::beginTransaction();

        try {
            $oldData = $assetMaintenanceSchedule->toArray();

            $this->scheduleService->delete($assetMaintenanceSchedule);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'asset-maintenance-schedules',
                'action' => 'delete',
                'model' => 'AssetMaintenanceSchedule',
                'model_id' => $oldData['id'],
                'description' => 'Maintenance schedule "' . $oldData['title'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Maintenance schedule deleted successfully.'
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

        $model = AssetMaintenanceSchedule::find($id);
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
     * Dropdown data shared by create/edit. Disposed assets are excluded —
     * there is nothing left to maintain — but an asset may carry any number
     * of schedules (servicing and inspection are different jobs), so
     * nothing else is filtered out.
     */
    protected function formData(): array
    {
        return [
            'assets' => Asset::active()->where('asset_status', '!=', 'disposed')->orderBy('asset_code')->get(),
            'vendors' => Vendor::active()->orderBy('name')->get(),
            'employees' => Employee::active()->orderBy('first_name')->get(),
        ];
    }
}
