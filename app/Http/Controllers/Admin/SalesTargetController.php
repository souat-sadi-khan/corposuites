<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SalesTargetRequest;
use App\Models\Admin;
use App\Models\SalesTarget;
use App\Services\SalesTargetService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class SalesTargetController extends Controller
{
    use ActivityLogger;

    protected $salesTargetService;

    public function __construct(SalesTargetService $salesTargetService)
    {
        $this->salesTargetService = $salesTargetService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = SalesTarget::query()->with('admin');

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by salesperson
            if ($request->admin_id) {
                $query->where('admin_id', $request->admin_id);
            }

            // Filter by period type
            if ($request->period_type) {
                $query->where('period_type', $request->period_type);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->whereHas('admin', function ($aq) use ($search) {
                    $aq->where('name', 'like', "%{$search}%");
                });
            }

            $query->orderBy('period_start', 'DESC')->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.sales-targets.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('admin_name', function ($row) {
                    return '<b class="tl-name-txt">' . ($row->admin->name ?? '-') . '</b><br><small>' . ucfirst($row->period_type) . '</small>';
                })
                ->addColumn('period_label', function ($row) {
                    return $row->period_start->format('d M, Y') . ' - ' . $row->period_end->format('d M, Y');
                })
                ->addColumn('target_amount_formatted', function ($row) {
                    return number_format($row->target_amount, 2);
                })
                ->addColumn('achieved_amount_formatted', function ($row) {
                    return number_format($row->achieved_amount, 2);
                })
                ->addColumn('achievement_badge', function ($row) {
                    $percent = $row->target_amount > 0 ? round(($row->achieved_amount / $row->target_amount) * 100) : 0;
                    $color = $percent >= 100 ? 'success' : ($percent >= 50 ? 'warning' : 'danger');
                    return '<span class="badge bg-' . $color . '">' . $percent . '%</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.sales-targets.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'admin_name', 'achievement_badge', 'action'])
                ->make(true);
        }

        $admins = Admin::all();

        return view('admin.sales-targets.index', compact('admins'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $admins = Admin::all();

        return view('admin.sales-targets.create', compact('admins'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SalesTargetRequest $request)
    {
        $salesTarget = $this->salesTargetService->create($request->validated());

        $this->logActivity([
            'actor_type' => 'admin',
            'actor_id' => auth()->guard('admin')->id(),
            'module' => 'sales-targets',
            'action' => 'create',
            'model' => 'SalesTarget',
            'model_id' => $salesTarget->id,
            'description' => 'Sales Target created for admin #' . $salesTarget->admin_id,
            'new_data' => $salesTarget->toArray(),
            'old_data' => null
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Sales target created successfully.'
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SalesTarget $salesTarget)
    {
        $admins = Admin::all();

        return view('admin.sales-targets.edit', compact('salesTarget', 'admins'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SalesTargetRequest $request, SalesTarget $salesTarget)
    {
        $oldData = $salesTarget->toArray();
        $updatedSalesTarget = $this->salesTargetService->update($salesTarget, $request->validated());

        $this->logActivity([
            'actor_type' => 'admin',
            'actor_id' => auth()->guard('admin')->id(),
            'module' => 'sales-targets',
            'action' => 'update',
            'model' => 'SalesTarget',
            'model_id' => $salesTarget->id,
            'description' => 'Sales Target #' . $salesTarget->id . ' updated',
            'new_data' => $updatedSalesTarget->toArray(),
            'old_data' => $oldData
        ]);

        return response()->json([
            'status' => true,
            'goto' => route('admin.sales-targets.index'),
            'message' => 'Sales target updated successfully.'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SalesTarget $salesTarget)
    {
        $oldData = $salesTarget->toArray();

        $this->salesTargetService->delete($salesTarget);

        $this->logActivity([
            'actor_type' => 'admin',
            'actor_id' => auth()->guard('admin')->id(),
            'module' => 'sales-targets',
            'action' => 'delete',
            'model' => 'SalesTarget',
            'model_id' => $oldData['id'],
            'description' => 'Sales Target #' . $oldData['id'] . ' deleted',
            'new_data' => null,
            'old_data' => $oldData
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Sales target deleted successfully.'
        ]);
    }

    /**
     * Update status (AJAX switch toggle)
     */
    public function updateStatus(Request $request, int $id)
    {
        $request->validate([
            'status' => 'required|boolean',
        ]);

        $model = SalesTarget::find($id);
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
