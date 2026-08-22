<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Images;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PerformanceReviewRequest;
use App\Models\Employee;
use App\Models\PerformanceReview;
use App\Services\PerformanceReviewService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class PerformanceReviewController extends Controller
{
    use ActivityLogger;

    protected $performanceReviewService;

    public function __construct(PerformanceReviewService $performanceReviewService)
    {
        $this->performanceReviewService = $performanceReviewService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = PerformanceReview::query()->with(['employee', 'reviewer']);

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
                $query->whereHas('employee', function ($eq) use ($search) {
                    $eq->where('first_name', 'like', "%{$search}%")
                       ->orWhere('last_name', 'like', "%{$search}%")
                       ->orWhere('employee_code', 'like', "%{$search}%");
                });
            }

            $query->orderBy('review_period_end', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.performance-reviews.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
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
                ->addColumn('reviewer_name', function ($row) {
                    return $row->reviewer ? $row->reviewer->full_name : '-';
                })
                ->addColumn('period', function ($row) {
                    $start = $row->review_period_start ? $row->review_period_start->format('d-m-Y') : '-';
                    $end = $row->review_period_end ? $row->review_period_end->format('d-m-Y') : '-';
                    return $start . ' to ' . $end;
                })
                ->addColumn('rating_badge', function ($row) {
                    $color = $row->rating >= 4 ? 'success' : ($row->rating >= 2.5 ? 'warning' : 'danger');
                    return '<span class="badge bg-' . $color . '-subtle text-' . $color . '">' . number_format($row->rating, 1) . ' / 5</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.performance-reviews.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'employee_name', 'rating_badge', 'action'])
                ->make(true);
        }

        return view('admin.performance-reviews.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $employees = Employee::active()->get();

        return view('admin.performance-reviews.create', compact('employees'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PerformanceReviewRequest $request)
    {
        DB::beginTransaction();

        try {
            $performanceReview = $this->performanceReviewService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'performance-reviews',
                'action' => 'create',
                'model' => 'PerformanceReview',
                'model_id' => $performanceReview->id,
                'description' => 'Performance review created for employee #' . $performanceReview->employee_id,
                'new_data' => $performanceReview->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Performance review created successfully.'
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
    public function edit(PerformanceReview $performanceReview)
    {
        $employees = Employee::active()->get();

        return view('admin.performance-reviews.edit', compact('performanceReview', 'employees'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PerformanceReviewRequest $request, PerformanceReview $performanceReview)
    {
        DB::beginTransaction();

        try {
            $oldData = $performanceReview->toArray();
            $updatedPerformanceReview = $this->performanceReviewService->update($performanceReview, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'performance-reviews',
                'action' => 'update',
                'model' => 'PerformanceReview',
                'model_id' => $performanceReview->id,
                'description' => 'Performance review updated for employee #' . $performanceReview->employee_id,
                'new_data' => $updatedPerformanceReview->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.performance-reviews.index'),
                'message' => 'Performance review updated successfully.'
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
    public function destroy(PerformanceReview $performanceReview)
    {
        DB::beginTransaction();

        try {
            $oldData = $performanceReview->toArray();

            $this->performanceReviewService->delete($performanceReview);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'performance-reviews',
                'action' => 'delete',
                'model' => 'PerformanceReview',
                'model_id' => $oldData['id'],
                'description' => 'Performance review deleted for employee #' . $oldData['employee_id'],
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Performance review deleted successfully.'
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

        $model = PerformanceReview::find($id);
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
