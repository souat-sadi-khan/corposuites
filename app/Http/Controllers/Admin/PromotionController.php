<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PromotionRequest;
use App\Models\Employee;
use App\Models\Promotion;
use App\Services\PromotionService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class PromotionController extends Controller
{
    use ActivityLogger;

    protected $promotionService;

    public function __construct(PromotionService $promotionService)
    {
        $this->promotionService = $promotionService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Promotion::query()->with('employee');

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
                    $q->where('to_designation', 'like', "%{$search}%")
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
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.promotions.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('employee_name', function ($row) {
                    return $row->employee ? $row->employee->full_name . '<br><small>' . $row->employee->employee_code . '</small>' : '-';
                })
                ->addColumn('designation_change', function ($row) {
                    return ($row->from_designation ?? '-') . ' <i class="ri-arrow-right-line"></i> <b>' . $row->to_designation . '</b>';
                })
                ->addColumn('salary_change', function ($row) {
                    $from = $row->from_salary !== null ? number_format($row->from_salary, 2) : '-';
                    $to = $row->to_salary !== null ? number_format($row->to_salary, 2) : '-';
                    return $from . ' <i class="ri-arrow-right-line"></i> ' . $to;
                })
                ->addColumn('promotion_date_formatted', function ($row) {
                    return $row->promotion_date ? $row->promotion_date->format('d-m-Y') : '-';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.promotions.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'employee_name', 'designation_change', 'action'])
                ->make(true);
        }

        return view('admin.promotions.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $employees = Employee::active()->get();

        return view('admin.promotions.create', compact('employees'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PromotionRequest $request)
    {
        DB::beginTransaction();

        try {
            $promotion = $this->promotionService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'promotions',
                'action' => 'create',
                'model' => 'Promotion',
                'model_id' => $promotion->id,
                'description' => 'Promotion created for employee #' . $promotion->employee_id,
                'new_data' => $promotion->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Promotion recorded successfully.'
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
    public function edit(Promotion $promotion)
    {
        $employees = Employee::active()->get();

        return view('admin.promotions.edit', compact('promotion', 'employees'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PromotionRequest $request, Promotion $promotion)
    {
        DB::beginTransaction();

        try {
            $oldData = $promotion->toArray();
            $updatedPromotion = $this->promotionService->update($promotion, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'promotions',
                'action' => 'update',
                'model' => 'Promotion',
                'model_id' => $promotion->id,
                'description' => 'Promotion updated for employee #' . $promotion->employee_id,
                'new_data' => $updatedPromotion->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.promotions.index'),
                'message' => 'Promotion updated successfully.'
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
    public function destroy(Promotion $promotion)
    {
        DB::beginTransaction();

        try {
            $oldData = $promotion->toArray();

            $this->promotionService->delete($promotion);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'promotions',
                'action' => 'delete',
                'model' => 'Promotion',
                'model_id' => $oldData['id'],
                'description' => 'Promotion deleted for employee #' . $oldData['employee_id'],
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Promotion deleted successfully.'
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

        $model = Promotion::find($id);
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
