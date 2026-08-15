<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FollowUpRequest;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Contact;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Services\FollowUpService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class FollowUpController extends Controller
{
    use ActivityLogger;

    protected $followUpService;

    public function __construct(FollowUpService $followUpService)
    {
        $this->followUpService = $followUpService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = FollowUp::query()->with(['lead', 'contact', 'company', 'opportunity', 'assignedTo']);

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by completion
            if ($request->is_completed !== null && $request->is_completed !== '') {
                $query->where('is_completed', $request->is_completed);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('notes', 'like', "%{$search}%");
                });
            }

            $query->orderBy('remind_at', 'ASC')->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.follow-ups.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('completed_badge', function ($row) {
                    $checked = $row->is_completed ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.follow-ups.complete', $row->id) . '" class="switch form-check-input completed-switch" type="checkbox" role="switch" name="is_completed" id="completed' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('title', function ($row) {
                    return '<b class="tl-name-txt">' . $row->title . '</b>';
                })
                ->addColumn('remind_at_formatted', function ($row) {
                    return $row->remind_at ? $row->remind_at->format('d M, Y h:i A') : '-';
                })
                ->addColumn('assigned_to_name', function ($row) {
                    return $row->assignedTo->name ?? 'Unassigned';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.follow-ups.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'completed_badge', 'title', 'action'])
                ->make(true);
        }

        return view('admin.follow-ups.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $leads = Lead::active()->get();
        $contacts = Contact::active()->get();
        $companies = Company::active()->get();
        $opportunities = Opportunity::active()->get();
        $admins = Admin::all();

        return view('admin.follow-ups.create', compact('leads', 'contacts', 'companies', 'opportunities', 'admins'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(FollowUpRequest $request)
    {
        DB::beginTransaction();

        try {
            $followUp = $this->followUpService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'follow-ups',
                'action' => 'create',
                'model' => 'FollowUp',
                'model_id' => $followUp->id,
                'description' => 'Follow up "' . $followUp->title . '" created',
                'new_data' => $followUp->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Follow up created successfully.'
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
    public function edit(FollowUp $followUp)
    {
        $leads = Lead::active()->get();
        $contacts = Contact::active()->get();
        $companies = Company::active()->get();
        $opportunities = Opportunity::active()->get();
        $admins = Admin::all();

        return view('admin.follow-ups.edit', compact('followUp', 'leads', 'contacts', 'companies', 'opportunities', 'admins'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(FollowUpRequest $request, FollowUp $followUp)
    {
        DB::beginTransaction();

        try {
            $oldData = $followUp->toArray();
            $updatedFollowUp = $this->followUpService->update($followUp, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'follow-ups',
                'action' => 'update',
                'model' => 'FollowUp',
                'model_id' => $followUp->id,
                'description' => 'Follow up "' . $followUp->title . '" updated',
                'new_data' => $updatedFollowUp->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.follow-ups.index'),
                'message' => 'Follow up updated successfully.'
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
    public function destroy(FollowUp $followUp)
    {
        DB::beginTransaction();

        try {
            $oldData = $followUp->toArray();

            $this->followUpService->delete($followUp);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'follow-ups',
                'action' => 'delete',
                'model' => 'FollowUp',
                'model_id' => $oldData['id'],
                'description' => 'Follow up "' . $oldData['title'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Follow up deleted successfully.'
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

        $model = FollowUp::find($id);
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
     * Update completion state (AJAX switch toggle)
     */
    public function updateCompleted(Request $request, int $id)
    {
        $request->validate([
            'is_completed' => 'required|boolean',
        ]);

        $model = FollowUp::find($id);
        if (!$model) {
            return response()->json([
                'success' => false,
                'message' => 'Record not found.'
            ]);
        }

        $this->followUpService->markCompleted($model, $request->boolean('is_completed'));

        return response()->json([
            'success' => true,
            'message' => 'Follow up completion updated successfully.'
        ]);
    }
}
