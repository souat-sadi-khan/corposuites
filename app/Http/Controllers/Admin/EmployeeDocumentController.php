<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EmployeeDocumentRequest;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Services\EmployeeDocumentService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class EmployeeDocumentController extends Controller
{
    use ActivityLogger;

    protected $employeeDocumentService;

    public function __construct(EmployeeDocumentService $employeeDocumentService)
    {
        $this->employeeDocumentService = $employeeDocumentService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = EmployeeDocument::query()->with('employee');

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
                    $q->where('title', 'like', "%{$search}%")
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
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.employee-documents.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('title', function ($row) {
                    return '<b class="tl-name-txt">' . $row->title . '</b><br><small>' . ($row->description ?? '') . '</small>';
                })
                ->addColumn('employee_name', function ($row) {
                    return $row->employee ? $row->employee->full_name . '<br><small>' . $row->employee->employee_code . '</small>' : '-';
                })
                ->addColumn('expiry', function ($row) {
                    return $row->expiry_date ? $row->expiry_date->format('d-m-Y') : '-';
                })
                ->addColumn('file_link', function ($row) {
                    return '<a href="' . asset('storage/' . $row->file_path) . '" target="_blank" class="tl-icon-btn" title="View File"><i class="ri-download-2-line"></i></a>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.employee-documents.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'title', 'employee_name', 'file_link', 'action'])
                ->make(true);
        }

        return view('admin.employee-documents.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $employees = Employee::active()->get();

        return view('admin.employee-documents.create', compact('employees'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(EmployeeDocumentRequest $request)
    {
        DB::beginTransaction();

        try {
            $employeeDocument = $this->employeeDocumentService->create($request->validated(), $request->file('file'));

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'employee-documents',
                'action' => 'create',
                'model' => 'EmployeeDocument',
                'model_id' => $employeeDocument->id,
                'description' => 'Employee Document "' . $employeeDocument->title . '" created',
                'new_data' => $employeeDocument->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Employee document created successfully.'
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
    public function edit(EmployeeDocument $employeeDocument)
    {
        $employees = Employee::active()->get();

        return view('admin.employee-documents.edit', compact('employeeDocument', 'employees'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(EmployeeDocumentRequest $request, EmployeeDocument $employeeDocument)
    {
        DB::beginTransaction();

        try {
            $oldData = $employeeDocument->toArray();
            $updatedEmployeeDocument = $this->employeeDocumentService->update($employeeDocument, $request->validated(), $request->file('file'));

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'employee-documents',
                'action' => 'update',
                'model' => 'EmployeeDocument',
                'model_id' => $employeeDocument->id,
                'description' => 'Employee Document "' . $employeeDocument->title . '" updated',
                'new_data' => $updatedEmployeeDocument->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.employee-documents.index'),
                'message' => 'Employee document updated successfully.'
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
    public function destroy(EmployeeDocument $employeeDocument)
    {
        DB::beginTransaction();

        try {
            $oldData = $employeeDocument->toArray();

            $this->employeeDocumentService->delete($employeeDocument);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'employee-documents',
                'action' => 'delete',
                'model' => 'EmployeeDocument',
                'model_id' => $oldData['id'],
                'description' => 'Employee Document "' . $oldData['title'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Employee document deleted successfully.'
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

        $model = EmployeeDocument::find($id);
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
