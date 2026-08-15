<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CustomerRequest;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\PaymentTerm;
use App\Services\CustomerService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class CustomerController extends Controller
{
    use ActivityLogger;

    protected $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Customer::query()->with(['customerGroup', 'paymentTerm']);

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by customer group
            if ($request->customer_group_id) {
                $query->where('customer_group_id', $request->customer_group_id);
            }

            // Filter by payment term
            if ($request->payment_term_id) {
                $query->where('payment_term_id', $request->payment_term_id);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('customer_code', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('company_name', 'like', "%{$search}%");
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.customers.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('name', function ($row) {
                    return '<b class="tl-name-txt">' . $row->name . '</b><br><small>' . $row->customer_code . '</small>';
                })
                ->addColumn('contact', function ($row) {
                    return ($row->email ?? '-') . '<br><small>' . ($row->phone ?? '-') . '</small>';
                })
                ->addColumn('company_name', function ($row) {
                    return $row->company_name ?? '-';
                })
                ->addColumn('customer_group_name', function ($row) {
                    return $row->customerGroup->name ?? '-';
                })
                ->addColumn('credit_limit_label', function ($row) {
                    return $row->credit_limit_enabled ? number_format($row->credit_limit, 2) : 'No Limit';
                })
                ->addColumn('payment_term_name', function ($row) {
                    return $row->paymentTerm->name ?? '-';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.customers.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'name', 'contact', 'action'])
                ->make(true);
        }

        $customerGroups = CustomerGroup::active()->get();
        $paymentTerms = PaymentTerm::active()->get();

        return view('admin.customers.index', compact('customerGroups', 'paymentTerms'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $customerGroups = CustomerGroup::active()->get();
        $paymentTerms = PaymentTerm::active()->get();

        return view('admin.customers.create', compact('customerGroups', 'paymentTerms'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CustomerRequest $request)
    {
        DB::beginTransaction();

        try {
            $customer = $this->customerService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'customers',
                'action' => 'create',
                'model' => 'Customer',
                'model_id' => $customer->id,
                'description' => 'Customer "' . $customer->name . '" created',
                'new_data' => $customer->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Customer created successfully.'
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
    public function edit(Customer $customer)
    {
        $customerGroups = CustomerGroup::active()->get();
        $paymentTerms = PaymentTerm::active()->get();

        return view('admin.customers.edit', compact('customer', 'customerGroups', 'paymentTerms'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CustomerRequest $request, Customer $customer)
    {
        DB::beginTransaction();

        try {
            $oldData = $customer->toArray();
            $updatedCustomer = $this->customerService->update($customer, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'customers',
                'action' => 'update',
                'model' => 'Customer',
                'model_id' => $customer->id,
                'description' => 'Customer "' . $customer->name . '" updated',
                'new_data' => $updatedCustomer->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.customers.index'),
                'message' => 'Customer updated successfully.'
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
    public function destroy(Customer $customer)
    {
        DB::beginTransaction();

        try {
            $oldData = $customer->toArray();

            $this->customerService->delete($customer);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'customers',
                'action' => 'delete',
                'model' => 'Customer',
                'model_id' => $oldData['id'],
                'description' => 'Customer "' . $oldData['name'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Customer deleted successfully.'
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

        $model = Customer::find($id);
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
