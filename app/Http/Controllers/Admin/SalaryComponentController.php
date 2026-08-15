<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SalaryComponentRequest;
use App\Models\SalaryComponent;
use App\Services\SalaryComponentService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class SalaryComponentController extends Controller
{
    use ActivityLogger;

    protected $salaryComponentService;

    public function __construct(SalaryComponentService $salaryComponentService)
    {
        $this->salaryComponentService = $salaryComponentService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = SalaryComponent::query();

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
                      ->orWhere('code', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.salary-components.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('name', function ($row) {
                    return '<b class="tl-name-txt">' . $row->name . '</b><br><small>' . $row->code . '</small>';
                })
                ->addColumn('type_badge', function ($row) {
                    return $row->type === 'earning'
                        ? '<span class="badge bg-success-subtle text-success">Earning</span>'
                        : '<span class="badge bg-danger-subtle text-danger">Deduction</span>';
                })
                ->addColumn('value_formatted', function ($row) {
                    return $row->calculation_type === 'percentage'
                        ? number_format($row->value, 2) . ' %'
                        : number_format($row->value, 2);
                })
                ->addColumn('action', function ($row) {
                    return view('admin.salary-components.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'name', 'type_badge', 'action'])
                ->make(true);
        }

        return view('admin.salary-components.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.salary-components.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SalaryComponentRequest $request)
    {
        DB::beginTransaction();

        try {
            $salaryComponent = $this->salaryComponentService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'salary-components',
                'action' => 'create',
                'model' => 'SalaryComponent',
                'model_id' => $salaryComponent->id,
                'description' => 'Salary Component "' . $salaryComponent->name . '" created',
                'new_data' => $salaryComponent->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Salary component created successfully.'
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
    public function edit(SalaryComponent $salaryComponent)
    {
        return view('admin.salary-components.edit', compact('salaryComponent'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SalaryComponentRequest $request, SalaryComponent $salaryComponent)
    {
        DB::beginTransaction();

        try {
            $oldData = $salaryComponent->toArray();
            $updatedSalaryComponent = $this->salaryComponentService->update($salaryComponent, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'salary-components',
                'action' => 'update',
                'model' => 'SalaryComponent',
                'model_id' => $salaryComponent->id,
                'description' => 'Salary Component "' . $salaryComponent->name . '" updated',
                'new_data' => $updatedSalaryComponent->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.salary-components.index'),
                'message' => 'Salary component updated successfully.'
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
    public function destroy(SalaryComponent $salaryComponent)
    {
        DB::beginTransaction();

        try {
            $oldData = $salaryComponent->toArray();

            $this->salaryComponentService->delete($salaryComponent);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'salary-components',
                'action' => 'delete',
                'model' => 'SalaryComponent',
                'model_id' => $oldData['id'],
                'description' => 'Salary Component "' . $oldData['name'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Salary component deleted successfully.'
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

        $model = SalaryComponent::find($id);
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
