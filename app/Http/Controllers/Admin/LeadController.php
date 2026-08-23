<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LeadRequest;
use App\Models\Admin;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Services\LeadService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class LeadController extends Controller
{
    use ActivityLogger;

    protected $leadService;

    public function __construct(LeadService $leadService)
    {
        $this->leadService = $leadService;
    }

    /** Display a modal with guidance for managing leads. */
    public function howTo()
    {
        return view('admin.leads.doc');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Lead::query()->with(['leadSource', 'leadStatus', 'assignedTo']);

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by lead source
            if ($request->lead_source_id) {
                $query->where('lead_source_id', $request->lead_source_id);
            }

            // Filter by lead status
            if ($request->lead_status_id) {
                $query->where('lead_status_id', $request->lead_status_id);
            }

            if ($request->assigned_to) {
                $query->where('assigned_to', $request->assigned_to);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('company_name', 'like', "%{$search}%");
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.leads.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('name', function ($row) {
                    return '<b class="tl-name-txt">' . $row->name . '</b><br><small>' . ($row->company_name ?? '-') . '</small>';
                })
                ->addColumn('contact', function ($row) {
                    return ($row->email ?? '-') . '<br><small>' . ($row->phone ?? '-') . '</small>';
                })
                ->addColumn('lead_source_name', function ($row) {
                    return $row->leadSource->name ?? '-';
                })
                ->addColumn('lead_status_badge', function ($row) {
                    return $row->leadStatus->name ?? '-';
                })
                ->addColumn('assigned_to_name', function ($row) {
                    return $row->assignedTo->name ?? 'Unassigned';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.leads.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'name', 'contact', 'action'])
                ->make(true);
        }

        $leadSources = LeadSource::active()->get();
        $leadStatuses = LeadStatus::active()->get();
        $admins = Admin::all();

        return view('admin.leads.index', compact('leadSources', 'leadStatuses', 'admins'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $leadSources = LeadSource::active()->get();
        $leadStatuses = LeadStatus::active()->get();
        $admins = Admin::all();

        return view('admin.leads.create', compact('leadSources', 'leadStatuses', 'admins'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(LeadRequest $request)
    {
        DB::beginTransaction();

        try {
            $lead = $this->leadService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'leads',
                'action' => 'create',
                'model' => 'Lead',
                'model_id' => $lead->id,
                'description' => 'Lead "' . $lead->name . '" created',
                'new_data' => $lead->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Lead created successfully.'
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
    public function edit(Lead $lead)
    {
        $leadSources = LeadSource::active()->get();
        $leadStatuses = LeadStatus::active()->get();
        $admins = Admin::all();

        return view('admin.leads.edit', compact('lead', 'leadSources', 'leadStatuses', 'admins'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(LeadRequest $request, Lead $lead)
    {
        DB::beginTransaction();

        try {
            $oldData = $lead->toArray();
            $updatedLead = $this->leadService->update($lead, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'leads',
                'action' => 'update',
                'model' => 'Lead',
                'model_id' => $lead->id,
                'description' => 'Lead "' . $lead->name . '" updated',
                'new_data' => $updatedLead->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.leads.index'),
                'message' => 'Lead updated successfully.'
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
    public function destroy(Lead $lead)
    {
        DB::beginTransaction();

        try {
            $oldData = $lead->toArray();

            $this->leadService->delete($lead);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'leads',
                'action' => 'delete',
                'model' => 'Lead',
                'model_id' => $oldData['id'],
                'description' => 'Lead "' . $oldData['name'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Lead deleted successfully.'
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

        $model = Lead::find($id);
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
