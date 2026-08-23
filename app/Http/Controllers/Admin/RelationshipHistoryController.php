<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RelationshipHistoryRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\RelationshipHistory;
use App\Services\RelationshipHistoryService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class RelationshipHistoryController extends Controller
{
    use ActivityLogger;

    protected $relationshipHistoryService;

    public function __construct(RelationshipHistoryService $relationshipHistoryService)
    {
        $this->relationshipHistoryService = $relationshipHistoryService;
    }

    /** Display a modal with guidance for relationship history. */
    public function howTo()
    {
        return view('admin.relationship-histories.doc');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = RelationshipHistory::query()->with(['lead', 'contact', 'company', 'createdBy']);

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by type
            if ($request->type) {
                $query->where('type', $request->type);
            }

            if (in_array($request->related_type, ['lead', 'contact', 'company'], true)) {
                $query->whereNotNull($request->related_type . '_id');
            }

            if ($request->interaction_date_from) {
                $query->whereDate('interaction_date', '>=', $request->interaction_date_from);
            }

            if ($request->interaction_date_to) {
                $query->whereDate('interaction_date', '<=', $request->interaction_date_to);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('subject', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $query->orderBy('interaction_date', 'DESC')->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.relationship-histories.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('subject', function ($row) {
                    return '<b class="tl-name-txt">' . $row->subject . '</b><br><small>' . ucfirst($row->type) . '</small>';
                })
                ->addColumn('related_to', function ($row) {
                    if ($row->lead) {
                        return 'Lead: ' . $row->lead->name;
                    }
                    if ($row->contact) {
                        return 'Contact: ' . $row->contact->name;
                    }
                    if ($row->company) {
                        return 'Company: ' . $row->company->name;
                    }
                    return '-';
                })
                ->addColumn('interaction_date_formatted', function ($row) {
                    return $row->interaction_date ? $row->interaction_date->format('d M, Y') : '-';
                })
                ->addColumn('created_by_name', function ($row) {
                    return $row->createdBy->name ?? '-';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.relationship-histories.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'subject', 'action'])
                ->make(true);
        }

        return view('admin.relationship-histories.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $leads = Lead::active()->get();
        $contacts = Contact::active()->get();
        $companies = Company::active()->get();

        return view('admin.relationship-histories.create', compact('leads', 'contacts', 'companies'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RelationshipHistoryRequest $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->validated();
            $data['created_by'] = auth()->guard('admin')->id();

            $relationshipHistory = $this->relationshipHistoryService->create($data);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'relationship-histories',
                'action' => 'create',
                'model' => 'RelationshipHistory',
                'model_id' => $relationshipHistory->id,
                'description' => 'Relationship history "' . $relationshipHistory->subject . '" created',
                'new_data' => $relationshipHistory->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Relationship history created successfully.'
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
    public function edit(RelationshipHistory $relationshipHistory)
    {
        $leads = Lead::active()->get();
        $contacts = Contact::active()->get();
        $companies = Company::active()->get();

        return view('admin.relationship-histories.edit', compact('relationshipHistory', 'leads', 'contacts', 'companies'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RelationshipHistoryRequest $request, RelationshipHistory $relationshipHistory)
    {
        DB::beginTransaction();

        try {
            $oldData = $relationshipHistory->toArray();
            $updatedRelationshipHistory = $this->relationshipHistoryService->update($relationshipHistory, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'relationship-histories',
                'action' => 'update',
                'model' => 'RelationshipHistory',
                'model_id' => $relationshipHistory->id,
                'description' => 'Relationship history "' . $relationshipHistory->subject . '" updated',
                'new_data' => $updatedRelationshipHistory->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.relationship-histories.index'),
                'message' => 'Relationship history updated successfully.'
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
    public function destroy(RelationshipHistory $relationshipHistory)
    {
        DB::beginTransaction();

        try {
            $oldData = $relationshipHistory->toArray();

            $this->relationshipHistoryService->delete($relationshipHistory);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'relationship-histories',
                'action' => 'delete',
                'model' => 'RelationshipHistory',
                'model_id' => $oldData['id'],
                'description' => 'Relationship history "' . $oldData['subject'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Relationship history deleted successfully.'
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

        $model = RelationshipHistory::find($id);
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
