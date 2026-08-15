<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OpportunityRequest;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Services\OpportunityService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class OpportunityController extends Controller
{
    use ActivityLogger;

    protected $opportunityService;

    public function __construct(OpportunityService $opportunityService)
    {
        $this->opportunityService = $opportunityService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Opportunity::query()->with(['lead', 'contact', 'company', 'assignedTo']);

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by stage
            if ($request->stage) {
                $query->where('stage', $request->stage);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhereHas('company', function ($cq) use ($search) {
                          $cq->where('name', 'like', "%{$search}%");
                      });
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.opportunities.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('name', function ($row) {
                    return '<b class="tl-name-txt">' . $row->name . '</b><br><small>' . ($row->company->name ?? '-') . '</small>';
                })
                ->addColumn('amount_formatted', function ($row) {
                    return $row->amount !== null ? number_format($row->amount, 2) : '-';
                })
                ->addColumn('stage_badge', function ($row) {
                    return ucfirst($row->stage);
                })
                ->addColumn('assigned_to_name', function ($row) {
                    return $row->assignedTo->name ?? 'Unassigned';
                })
                ->addColumn('expected_close_date_formatted', function ($row) {
                    return $row->expected_close_date ? $row->expected_close_date->format('d M, Y') : '-';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.opportunities.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'name', 'action'])
                ->make(true);
        }

        return view('admin.opportunities.index', ['stages' => Opportunity::STAGES]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $leads = Lead::active()->get();
        $contacts = Contact::active()->get();
        $companies = Company::active()->get();
        $admins = Admin::all();

        return view('admin.opportunities.create', compact('leads', 'contacts', 'companies', 'admins'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(OpportunityRequest $request)
    {
        DB::beginTransaction();

        try {
            $opportunity = $this->opportunityService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'opportunities',
                'action' => 'create',
                'model' => 'Opportunity',
                'model_id' => $opportunity->id,
                'description' => 'Opportunity "' . $opportunity->name . '" created',
                'new_data' => $opportunity->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Opportunity created successfully.'
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
    public function edit(Opportunity $opportunity)
    {
        $leads = Lead::active()->get();
        $contacts = Contact::active()->get();
        $companies = Company::active()->get();
        $admins = Admin::all();

        return view('admin.opportunities.edit', compact('opportunity', 'leads', 'contacts', 'companies', 'admins'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(OpportunityRequest $request, Opportunity $opportunity)
    {
        DB::beginTransaction();

        try {
            $oldData = $opportunity->toArray();
            $updatedOpportunity = $this->opportunityService->update($opportunity, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'opportunities',
                'action' => 'update',
                'model' => 'Opportunity',
                'model_id' => $opportunity->id,
                'description' => 'Opportunity "' . $opportunity->name . '" updated',
                'new_data' => $updatedOpportunity->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.opportunities.index'),
                'message' => 'Opportunity updated successfully.'
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
    public function destroy(Opportunity $opportunity)
    {
        DB::beginTransaction();

        try {
            $oldData = $opportunity->toArray();

            $this->opportunityService->delete($opportunity);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'opportunities',
                'action' => 'delete',
                'model' => 'Opportunity',
                'model_id' => $oldData['id'],
                'description' => 'Opportunity "' . $oldData['name'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Opportunity deleted successfully.'
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

        $model = Opportunity::find($id);
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
     * Display the Sales Pipeline Kanban board (Opportunities grouped by stage).
     */
    public function kanban()
    {
        $stages = Opportunity::STAGES;

        $opportunities = Opportunity::active()
            ->with(['company', 'assignedTo'])
            ->orderBy('id', 'DESC')
            ->get()
            ->groupBy('stage');

        return view('admin.opportunities.kanban', compact('stages', 'opportunities'));
    }

    /**
     * Move an Opportunity to a different stage (drag-and-drop AJAX endpoint).
     */
    public function moveStage(Request $request, Opportunity $opportunity)
    {
        $request->validate([
            'stage' => ['required', Rule::in(Opportunity::STAGES)],
        ]);

        $oldStage = $opportunity->stage;

        $this->opportunityService->updateStage($opportunity, $request->stage);

        $this->logActivity([
            'actor_type' => 'admin',
            'actor_id' => auth()->guard('admin')->id(),
            'module' => 'opportunities',
            'action' => 'update',
            'model' => 'Opportunity',
            'model_id' => $opportunity->id,
            'description' => 'Opportunity "' . $opportunity->name . '" moved from "' . $oldStage . '" to "' . $request->stage . '"',
            'new_data' => ['stage' => $request->stage],
            'old_data' => ['stage' => $oldStage]
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Opportunity stage updated successfully.'
        ]);
    }
}
