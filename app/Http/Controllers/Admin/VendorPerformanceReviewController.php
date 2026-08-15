<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VendorPerformanceReviewRequest;
use App\Models\Admin;
use App\Models\Vendor;
use App\Models\VendorPerformanceReview;
use App\Services\VendorPerformanceReviewService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class VendorPerformanceReviewController extends Controller
{
    use ActivityLogger;

    protected $vendorPerformanceReviewService;

    public function __construct(VendorPerformanceReviewService $vendorPerformanceReviewService)
    {
        $this->vendorPerformanceReviewService = $vendorPerformanceReviewService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = VendorPerformanceReview::query()->with(['vendor', 'reviewedBy']);

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by vendor
            if ($request->vendor_id) {
                $query->where('vendor_id', $request->vendor_id);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->whereHas('vendor', function ($vq) use ($search) {
                    $vq->where('name', 'like', "%{$search}%");
                });
            }

            $query->orderBy('review_period_start', 'DESC')->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.vendor-performance-reviews.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('vendor_name', function ($row) {
                    return '<b class="tl-name-txt">' . ($row->vendor->name ?? '-') . '</b><br><small>' . ($row->reviewedBy->name ?? 'Unassigned') . '</small>';
                })
                ->addColumn('period_label', function ($row) {
                    return $row->review_period_start->format('d M, Y') . ' - ' . $row->review_period_end->format('d M, Y');
                })
                ->addColumn('overall_rating_badge', function ($row) {
                    $rating = (float) $row->overall_rating;
                    $color = $rating >= 4 ? 'success' : ($rating >= 2.5 ? 'warning' : 'danger');
                    return '<span class="badge bg-' . $color . '">' . number_format($rating, 1) . ' / 5</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.vendor-performance-reviews.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'vendor_name', 'overall_rating_badge', 'action'])
                ->make(true);
        }

        $vendors = Vendor::active()->get();

        return view('admin.vendor-performance-reviews.index', compact('vendors'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $vendors = Vendor::active()->get();
        $admins = Admin::all();

        return view('admin.vendor-performance-reviews.create', compact('vendors', 'admins'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(VendorPerformanceReviewRequest $request)
    {
        DB::beginTransaction();

        try {
            $vendorPerformanceReview = $this->vendorPerformanceReviewService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'vendor-performance-reviews',
                'action' => 'create',
                'model' => 'VendorPerformanceReview',
                'model_id' => $vendorPerformanceReview->id,
                'description' => 'Vendor Performance Review created for vendor #' . $vendorPerformanceReview->vendor_id,
                'new_data' => $vendorPerformanceReview->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Vendor performance review created successfully.'
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
    public function edit(VendorPerformanceReview $vendorPerformanceReview)
    {
        $vendors = Vendor::active()->get();
        $admins = Admin::all();

        return view('admin.vendor-performance-reviews.edit', compact('vendorPerformanceReview', 'vendors', 'admins'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(VendorPerformanceReviewRequest $request, VendorPerformanceReview $vendorPerformanceReview)
    {
        DB::beginTransaction();

        try {
            $oldData = $vendorPerformanceReview->toArray();
            $updatedVendorPerformanceReview = $this->vendorPerformanceReviewService->update($vendorPerformanceReview, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'vendor-performance-reviews',
                'action' => 'update',
                'model' => 'VendorPerformanceReview',
                'model_id' => $vendorPerformanceReview->id,
                'description' => 'Vendor Performance Review #' . $vendorPerformanceReview->id . ' updated',
                'new_data' => $updatedVendorPerformanceReview->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.vendor-performance-reviews.index'),
                'message' => 'Vendor performance review updated successfully.'
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
    public function destroy(VendorPerformanceReview $vendorPerformanceReview)
    {
        DB::beginTransaction();

        try {
            $oldData = $vendorPerformanceReview->toArray();

            $this->vendorPerformanceReviewService->delete($vendorPerformanceReview);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'vendor-performance-reviews',
                'action' => 'delete',
                'model' => 'VendorPerformanceReview',
                'model_id' => $oldData['id'],
                'description' => 'Vendor Performance Review #' . $oldData['id'] . ' deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Vendor performance review deleted successfully.'
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

        $model = VendorPerformanceReview::find($id);
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
