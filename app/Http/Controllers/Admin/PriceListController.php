<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PriceListRequest;
use App\Models\CustomerGroup;
use App\Models\PriceList;
use App\Models\Product;
use App\Services\PriceListService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class PriceListController extends Controller
{
    use ActivityLogger;

    protected $priceListService;

    public function __construct(PriceListService $priceListService)
    {
        $this->priceListService = $priceListService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = PriceList::query()->with('customerGroup')->withCount('items');

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by customer group
            if ($request->customer_group_id) {
                $query->where('customer_group_id', $request->customer_group_id);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where('name', 'like', "%{$search}%");
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.price-lists.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('name', function ($row) {
                    return '<b class="tl-name-txt">' . $row->name . '</b><br><small>' . ($row->customerGroup->name ?? 'All Customers') . '</small>';
                })
                ->addColumn('items_count_label', function ($row) {
                    return $row->items_count . ' product' . ($row->items_count == 1 ? '' : 's');
                })
                ->addColumn('action', function ($row) {
                    return view('admin.price-lists.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'name', 'action'])
                ->make(true);
        }

        $customerGroups = CustomerGroup::active()->get();

        return view('admin.price-lists.index', compact('customerGroups'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $customerGroups = CustomerGroup::active()->get();
        $products = Product::active()->get();

        return view('admin.price-lists.create', compact('customerGroups', 'products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PriceListRequest $request)
    {
        DB::beginTransaction();

        try {
            $priceList = $this->priceListService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'price-lists',
                'action' => 'create',
                'model' => 'PriceList',
                'model_id' => $priceList->id,
                'description' => 'Price List "' . $priceList->name . '" created',
                'new_data' => $priceList->load('items')->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Price list created successfully.'
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
    public function edit(PriceList $priceList)
    {
        $customerGroups = CustomerGroup::active()->get();
        $products = Product::active()->get();
        $priceList->load('items');

        return view('admin.price-lists.edit', compact('priceList', 'customerGroups', 'products'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PriceListRequest $request, PriceList $priceList)
    {
        DB::beginTransaction();

        try {
            $oldData = $priceList->load('items')->toArray();
            $updatedPriceList = $this->priceListService->update($priceList, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'price-lists',
                'action' => 'update',
                'model' => 'PriceList',
                'model_id' => $priceList->id,
                'description' => 'Price List "' . $priceList->name . '" updated',
                'new_data' => $updatedPriceList->load('items')->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.price-lists.index'),
                'message' => 'Price list updated successfully.'
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
    public function destroy(PriceList $priceList)
    {
        DB::beginTransaction();

        try {
            $oldData = $priceList->load('items')->toArray();

            $this->priceListService->delete($priceList);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'price-lists',
                'action' => 'delete',
                'model' => 'PriceList',
                'model_id' => $oldData['id'],
                'description' => 'Price List "' . $oldData['name'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Price list deleted successfully.'
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

        $model = PriceList::find($id);
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
