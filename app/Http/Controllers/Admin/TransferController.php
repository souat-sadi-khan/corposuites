<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Images;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TransferRequest;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Transfer;
use App\Services\TransferService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class TransferController extends Controller
{
    use ActivityLogger;

    protected $transferService;

    public function __construct(TransferService $transferService)
    {
        $this->transferService = $transferService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Transfer::query()->with('employee');

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by employee
            if ($request->employee_id) {
                $query->where('employee_id', $request->employee_id);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('to_department', 'like', "%{$search}%")
                      ->orWhere('to_designation', 'like', "%{$search}%")
                      ->orWhereHas('employee', function ($eq) use ($search) {
                          $eq->where('first_name', 'like', "%{$search}%")
                             ->orWhere('last_name', 'like', "%{$search}%")
                             ->orWhere('employee_code', 'like', "%{$search}%");
                      });
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.transfers.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('employee_name', function ($row) {
                    $avatar = Images::show($row->employee->photo);

                    return '
                        <div class="d-flex align-items-center">
                            <div class="mr-2 employee-avatar">
                                ' . $avatar . '
                            </div>
                            <div>
                                <b class="tl-name-txt">' . e($row->employee->full_name) . '</b>
                                <br>
                                <small>' . e($row->employee->employee_code) . '</small>
                            </div>
                        </div>
                    ';
                })
                ->addColumn('department_change', function ($row) {
                    return ($row->from_department ?? '-') . ' <i class="ri-arrow-right-line"></i> ' . ($row->to_department ?? '-');
                })
                ->addColumn('designation_change', function ($row) {
                    return ($row->from_designation ?? '-') . ' <i class="ri-arrow-right-line"></i> ' . ($row->to_designation ?? '-');
                })
                ->addColumn('transfer_date_formatted', function ($row) {
                    return $row->transfer_date ? $row->transfer_date->format('d-m-Y') : '-';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.transfers.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'employee_name', 'department_change', 'designation_change', 'action'])
                ->make(true);
        }

        return view('admin.transfers.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $employees = Employee::active()->get();
        $departments = Department::active()->get();

        return view('admin.transfers.create', compact('employees', 'departments'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TransferRequest $request)
    {
        DB::beginTransaction();

        try {
            $transfer = $this->transferService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'transfers',
                'action' => 'create',
                'model' => 'Transfer',
                'model_id' => $transfer->id,
                'description' => 'Transfer created for employee #' . $transfer->employee_id,
                'new_data' => $transfer->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Transfer recorded successfully.'
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
    public function edit(Transfer $transfer)
    {
        $employees = Employee::active()->get();

        return view('admin.transfers.edit', compact('transfer', 'employees'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TransferRequest $request, Transfer $transfer)
    {
        DB::beginTransaction();

        try {
            $oldData = $transfer->toArray();
            $updatedTransfer = $this->transferService->update($transfer, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'transfers',
                'action' => 'update',
                'model' => 'Transfer',
                'model_id' => $transfer->id,
                'description' => 'Transfer updated for employee #' . $transfer->employee_id,
                'new_data' => $updatedTransfer->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.transfers.index'),
                'message' => 'Transfer updated successfully.'
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
    public function destroy(Transfer $transfer)
    {
        DB::beginTransaction();

        try {
            $oldData = $transfer->toArray();

            $this->transferService->delete($transfer);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'transfers',
                'action' => 'delete',
                'model' => 'Transfer',
                'model_id' => $oldData['id'],
                'description' => 'Transfer deleted for employee #' . $oldData['employee_id'],
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Transfer deleted successfully.'
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

        $model = Transfer::find($id);
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
