<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SalesCommissionRequest;
use App\Models\Admin;
use App\Models\SalesCommission;
use App\Services\SalesCommissionService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class SalesCommissionController extends Controller
{
    use ActivityLogger;

    protected $salesCommissionService;

    public function __construct(SalesCommissionService $salesCommissionService)
    {
        $this->salesCommissionService = $salesCommissionService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = SalesCommission::query()->with('admin');

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by salesperson
            if ($request->admin_id) {
                $query->where('admin_id', $request->admin_id);
            }

            // Filter by payment status
            if ($request->payment_status) {
                $query->where('payment_status', $request->payment_status);
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
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.sales-commissions.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('admin_name', function ($row) {
                    return '<b class="tl-name-txt">' . ($row->admin->name ?? '-') . '</b><br><small>' . ucfirst($row->period_type) . ' &middot; ' . $row->commission_rate . '%</small>';
                })
                ->addColumn('period_label', function ($row) {
                    return $row->period_start->format('d M, Y') . ' - ' . $row->period_end->format('d M, Y');
                })
                ->addColumn('sales_amount_formatted', function ($row) {
                    return number_format($row->sales_amount, 2);
                })
                ->addColumn('commission_amount_formatted', function ($row) {
                    return number_format($row->commission_amount, 2);
                })
                ->addColumn('payment_status_badge', function ($row) {
                    return $row->payment_status === 'paid'
                        ? '<span class="badge bg-success">Paid' . ($row->payment_date ? ' (' . $row->payment_date->format('d M, Y') . ')' : '') . '</span>'
                        : '<span class="badge bg-secondary">Pending</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.sales-commissions.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'admin_name', 'payment_status_badge', 'action'])
                ->make(true);
        }

        $admins = Admin::all();

        return view('admin.sales-commissions.index', compact('admins'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $admins = Admin::all();

        return view('admin.sales-commissions.create', compact('admins'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SalesCommissionRequest $request)
    {
        $salesCommission = $this->salesCommissionService->create($request->validated());

        $this->logActivity([
            'actor_type' => 'admin',
            'actor_id' => auth()->guard('admin')->id(),
            'module' => 'sales-commissions',
            'action' => 'create',
            'model' => 'SalesCommission',
            'model_id' => $salesCommission->id,
            'description' => 'Sales Commission calculated for admin #' . $salesCommission->admin_id,
            'new_data' => $salesCommission->toArray(),
            'old_data' => null
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Sales commission calculated successfully.'
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SalesCommission $salesCommission)
    {
        $admins = Admin::all();

        return view('admin.sales-commissions.edit', compact('salesCommission', 'admins'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SalesCommissionRequest $request, SalesCommission $salesCommission)
    {
        if ($salesCommission->payment_status === 'paid') {
            return response()->json([
                'status' => false,
                'message' => 'A paid commission cannot be edited.'
            ], 422);
        }

        $oldData = $salesCommission->toArray();
        $updatedSalesCommission = $this->salesCommissionService->update($salesCommission, $request->validated());

        $this->logActivity([
            'actor_type' => 'admin',
            'actor_id' => auth()->guard('admin')->id(),
            'module' => 'sales-commissions',
            'action' => 'update',
            'model' => 'SalesCommission',
            'model_id' => $salesCommission->id,
            'description' => 'Sales Commission #' . $salesCommission->id . ' updated',
            'new_data' => $updatedSalesCommission->toArray(),
            'old_data' => $oldData
        ]);

        return response()->json([
            'status' => true,
            'goto' => route('admin.sales-commissions.index'),
            'message' => 'Sales commission updated successfully.'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SalesCommission $salesCommission)
    {
        $oldData = $salesCommission->toArray();

        $this->salesCommissionService->delete($salesCommission);

        $this->logActivity([
            'actor_type' => 'admin',
            'actor_id' => auth()->guard('admin')->id(),
            'module' => 'sales-commissions',
            'action' => 'delete',
            'model' => 'SalesCommission',
            'model_id' => $oldData['id'],
            'description' => 'Sales Commission #' . $oldData['id'] . ' deleted',
            'new_data' => null,
            'old_data' => $oldData
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Sales commission deleted successfully.'
        ]);
    }

    /**
     * Mark a commission as paid.
     */
    public function markAsPaid(SalesCommission $salesCommission)
    {
        $oldData = $salesCommission->toArray();

        $this->salesCommissionService->markAsPaid($salesCommission);

        $this->logActivity([
            'actor_type' => 'admin',
            'actor_id' => auth()->guard('admin')->id(),
            'module' => 'sales-commissions',
            'action' => 'mark-paid',
            'model' => 'SalesCommission',
            'model_id' => $salesCommission->id,
            'description' => 'Sales Commission #' . $salesCommission->id . ' marked as paid',
            'new_data' => $salesCommission->fresh()->toArray(),
            'old_data' => $oldData
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Commission marked as paid.'
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

        $model = SalesCommission::find($id);
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
