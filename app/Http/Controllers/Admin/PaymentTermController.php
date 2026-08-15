<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PaymentTermRequest;
use App\Models\PaymentTerm;
use App\Services\PaymentTermService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class PaymentTermController extends Controller
{
    use ActivityLogger;

    protected $paymentTermService;

    public function __construct(PaymentTermService $paymentTermService)
    {
        $this->paymentTermService = $paymentTermService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = PaymentTerm::query();

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

            $query->orderBy('days')->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.payment-terms.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('name', function ($row) {
                    return '<b class="tl-name-txt">' . $row->name . '</b><br><small>' . ($row->days == 0 ? 'Due on receipt' : $row->days . ' days') . '</small>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.payment-terms.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'name', 'action'])
                ->make(true);
        }

        return view('admin.payment-terms.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.payment-terms.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PaymentTermRequest $request)
    {
        DB::beginTransaction();

        try {
            $paymentTerm = $this->paymentTermService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'payment-terms',
                'action' => 'create',
                'model' => 'PaymentTerm',
                'model_id' => $paymentTerm->id,
                'description' => 'Payment Term "' . $paymentTerm->name . '" created',
                'new_data' => $paymentTerm->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Payment term created successfully.'
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
    public function edit(PaymentTerm $paymentTerm)
    {
        return view('admin.payment-terms.edit', compact('paymentTerm'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PaymentTermRequest $request, PaymentTerm $paymentTerm)
    {
        DB::beginTransaction();

        try {
            $oldData = $paymentTerm->toArray();
            $updatedPaymentTerm = $this->paymentTermService->update($paymentTerm, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'payment-terms',
                'action' => 'update',
                'model' => 'PaymentTerm',
                'model_id' => $paymentTerm->id,
                'description' => 'Payment Term "' . $paymentTerm->name . '" updated',
                'new_data' => $updatedPaymentTerm->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.payment-terms.index'),
                'message' => 'Payment term updated successfully.'
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
    public function destroy(PaymentTerm $paymentTerm)
    {
        DB::beginTransaction();

        try {
            $oldData = $paymentTerm->toArray();

            $this->paymentTermService->delete($paymentTerm);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'payment-terms',
                'action' => 'delete',
                'model' => 'PaymentTerm',
                'model_id' => $oldData['id'],
                'description' => 'Payment Term "' . $oldData['name'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Payment term deleted successfully.'
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

        $model = PaymentTerm::find($id);
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
