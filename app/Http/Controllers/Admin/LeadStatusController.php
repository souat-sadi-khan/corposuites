<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LeadStatusRequest;
use App\Models\LeadStatus;
use App\Services\LeadStatusService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class LeadStatusController extends Controller
{
    use ActivityLogger;

    protected $leadStatusService;

    public function __construct(LeadStatusService $leadStatusService)
    {
        $this->leadStatusService = $leadStatusService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = LeadStatus::query();

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
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.lead-statuses.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('name', function($row) {
                    return '<b class="tl-name-txt">'. $row->name . '</b><br><small>'. $row->description . '</small>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.lead-statuses.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'name', 'action'])
                ->make(true);
        }

        return view('admin.lead-statuses.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.lead-statuses.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(LeadStatusRequest $request)
    {
        DB::beginTransaction();

        try {
            $leadStatus = $this->leadStatusService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'lead-statuses',
                'action' => 'create',
                'model' => 'LeadStatus',
                'model_id' => $leadStatus->id,
                'description' => 'Lead Status "' . $leadStatus->name . '" created',
                'new_data' => $leadStatus->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Lead status created successfully.'
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
    public function edit(LeadStatus $leadStatus)
    {
        return view('admin.lead-statuses.edit', compact('leadStatus'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(LeadStatusRequest $request, LeadStatus $leadStatus)
    {
        DB::beginTransaction();

        try {
            $oldData = $leadStatus->toArray();
            $updatedLeadStatus = $this->leadStatusService->update($leadStatus, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'lead-statuses',
                'action' => 'update',
                'model' => 'LeadStatus',
                'model_id' => $leadStatus->id,
                'description' => 'Lead Status "' . $leadStatus->name . '" updated',
                'new_data' => $updatedLeadStatus->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.lead-statuses.index'),
                'message' => 'Lead status updated successfully.'
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
    public function destroy(LeadStatus $leadStatus)
    {
        DB::beginTransaction();

        try {
            $oldData = $leadStatus->toArray();

            $this->leadStatusService->delete($leadStatus);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'lead-statuses',
                'action' => 'delete',
                'model' => 'LeadStatus',
                'model_id' => $oldData['id'],
                'description' => 'Lead Status "' . $oldData['name'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Lead status deleted successfully.'
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

        $model = LeadStatus::find($id);
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
