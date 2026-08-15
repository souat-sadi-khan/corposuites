<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ActivityRequest;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Services\ActivityService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class ActivityController extends Controller
{
    use ActivityLogger;

    protected $activityService;

    public function __construct(ActivityService $activityService)
    {
        $this->activityService = $activityService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Activity::query()->with(['lead', 'contact', 'company', 'opportunity', 'assignedTo']);

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by type
            if ($request->type) {
                $query->where('type', $request->type);
            }

            // Filter by activity status
            if ($request->activity_status) {
                $query->where('activity_status', $request->activity_status);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('subject', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $query->orderBy('due_date', 'ASC')->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.activities.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('subject', function ($row) {
                    return '<b class="tl-name-txt">' . $row->subject . '</b><br><small>' . ucfirst($row->type) . '</small>';
                })
                ->addColumn('due_date_formatted', function ($row) {
                    return $row->due_date ? $row->due_date->format('d M, Y h:i A') : '-';
                })
                ->addColumn('activity_status_badge', function ($row) {
                    return ucfirst($row->activity_status);
                })
                ->addColumn('assigned_to_name', function ($row) {
                    return $row->assignedTo->name ?? 'Unassigned';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.activities.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'subject', 'action'])
                ->make(true);
        }

        return view('admin.activities.index');
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

        return view('admin.activities.create', compact('leads', 'contacts', 'companies', 'opportunities', 'admins'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ActivityRequest $request)
    {
        DB::beginTransaction();

        try {
            $activity = $this->activityService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'activities',
                'action' => 'create',
                'model' => 'Activity',
                'model_id' => $activity->id,
                'description' => 'Activity "' . $activity->subject . '" created',
                'new_data' => $activity->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Activity created successfully.'
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
    public function edit(Activity $activity)
    {
        $leads = Lead::active()->get();
        $contacts = Contact::active()->get();
        $companies = Company::active()->get();
        $opportunities = Opportunity::active()->get();
        $admins = Admin::all();

        return view('admin.activities.edit', compact('activity', 'leads', 'contacts', 'companies', 'opportunities', 'admins'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ActivityRequest $request, Activity $activity)
    {
        DB::beginTransaction();

        try {
            $oldData = $activity->toArray();
            $updatedActivity = $this->activityService->update($activity, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'activities',
                'action' => 'update',
                'model' => 'Activity',
                'model_id' => $activity->id,
                'description' => 'Activity "' . $activity->subject . '" updated',
                'new_data' => $updatedActivity->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.activities.index'),
                'message' => 'Activity updated successfully.'
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
    public function destroy(Activity $activity)
    {
        DB::beginTransaction();

        try {
            $oldData = $activity->toArray();

            $this->activityService->delete($activity);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'activities',
                'action' => 'delete',
                'model' => 'Activity',
                'model_id' => $oldData['id'],
                'description' => 'Activity "' . $oldData['subject'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Activity deleted successfully.'
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

        $model = Activity::find($id);
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
     * Update the activity's completion status (pending/completed/cancelled)
     */
    public function updateActivityStatus(Request $request, Activity $activity)
    {
        $request->validate([
            'activity_status' => ['required', Rule::in(Activity::ACTIVITY_STATUSES)],
        ]);

        $this->activityService->updateActivityStatus($activity, $request->activity_status);

        return response()->json([
            'success' => true,
            'message' => 'Activity status updated successfully.'
        ]);
    }
}
