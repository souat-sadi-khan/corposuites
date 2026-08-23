<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LeadSourceRequest;
use App\Models\LeadSource;
use App\Services\LeadSourceService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class LeadSourceController extends Controller
{
    use ActivityLogger;

    protected $leadSourceService;

    public function __construct(LeadSourceService $leadSourceService)
    {
        $this->leadSourceService = $leadSourceService;
    }

    /** Display a modal with guidance for managing lead sources. */
    public function howTo()
    {
        return view('admin.lead-sources.doc');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = LeadSource::query();

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            if ($request->filled('has_description')) {
                $request->boolean('has_description')
                    ? $query->whereNotNull('description')->where('description', '!=', '')
                    : $query->where(function ($query) {
                        $query->whereNull('description')->orWhere('description', '');
                    });
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
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.lead-sources.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('name', function($row) {
                    return '<b class="tl-name-txt">'. $row->name . '</b><br><small>'. $row->description . '</small>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.lead-sources.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'name', 'action'])
                ->make(true);
        }

        return view('admin.lead-sources.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.lead-sources.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(LeadSourceRequest $request)
    {
        DB::beginTransaction();

        try {
            $leadSource = $this->leadSourceService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'lead-sources',
                'action' => 'create',
                'model' => 'LeadSource',
                'model_id' => $leadSource->id,
                'description' => 'Lead Source "' . $leadSource->name . '" created',
                'new_data' => $leadSource->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Lead source created successfully.'
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
    public function edit(LeadSource $leadSource)
    {
        return view('admin.lead-sources.edit', compact('leadSource'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(LeadSourceRequest $request, LeadSource $leadSource)
    {
        DB::beginTransaction();

        try {
            $oldData = $leadSource->toArray();
            $updatedLeadSource = $this->leadSourceService->update($leadSource, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'lead-sources',
                'action' => 'update',
                'model' => 'LeadSource',
                'model_id' => $leadSource->id,
                'description' => 'Lead Source "' . $leadSource->name . '" updated',
                'new_data' => $updatedLeadSource->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.lead-sources.index'),
                'message' => 'Lead source updated successfully.'
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
    public function destroy(LeadSource $leadSource)
    {
        DB::beginTransaction();

        try {
            $oldData = $leadSource->toArray();

            $this->leadSourceService->delete($leadSource);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'lead-sources',
                'action' => 'delete',
                'model' => 'LeadSource',
                'model_id' => $oldData['id'],
                'description' => 'Lead Source "' . $oldData['name'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Lead source deleted successfully.'
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

        $model = LeadSource::find($id);
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
