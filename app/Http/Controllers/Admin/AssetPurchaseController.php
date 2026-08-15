<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssetPurchaseRequest;
use App\Models\Asset;
use App\Models\AssetPurchase;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use App\Services\AssetPurchaseService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class AssetPurchaseController extends Controller
{
    use ActivityLogger;

    protected $assetPurchaseService;

    public function __construct(AssetPurchaseService $assetPurchaseService)
    {
        $this->assetPurchaseService = $assetPurchaseService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = AssetPurchase::query()->with(['asset', 'vendor', 'purchaseOrder']);

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by vendor
            if ($request->vendor_id) {
                $query->where('vendor_id', $request->vendor_id);
            }

            // Filter by warranty state — computed from the expiry date, so
            // it is expressed as a date comparison here rather than a
            // column lookup (there is no stored warranty flag).
            if ($request->warranty === 'active') {
                $query->whereNotNull('warranty_expiry')->whereDate('warranty_expiry', '>=', now()->toDateString());
            } elseif ($request->warranty === 'expired') {
                $query->whereNotNull('warranty_expiry')->whereDate('warranty_expiry', '<', now()->toDateString());
            } elseif ($request->warranty === 'none') {
                $query->whereNull('warranty_expiry');
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                        ->orWhereHas('asset', function ($sub) use ($search) {
                            $sub->where('name', 'like', "%{$search}%")
                                ->orWhere('asset_code', 'like', "%{$search}%");
                        });
                });
            }

            $query->orderBy('purchase_date', 'DESC')->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.asset-purchases.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('asset_name', function ($row) {
                    if (! $row->asset) {
                        return '<span class="text-danger">Asset removed</span>';
                    }

                    return '<b class="tl-name-txt">' . e($row->asset->name) . '</b><br><small>' . e($row->asset->asset_code) . '</small>';
                })
                ->addColumn('vendor_name', function ($row) {
                    return $row->vendor ? e($row->vendor->name) : '-';
                })
                ->addColumn('source_document', function ($row) {
                    $parts = [];
                    if ($row->purchaseOrder) {
                        $parts[] = e($row->purchaseOrder->po_number);
                    }
                    if ($row->invoice_number) {
                        $parts[] = '<small>' . e($row->invoice_number) . '</small>';
                    }

                    return $parts ? implode('<br>', $parts) : '-';
                })
                ->addColumn('purchase_date_formatted', function ($row) {
                    return $row->purchase_date->format('d M, Y');
                })
                ->addColumn('total_cost_formatted', function ($row) {
                    $total = number_format($row->total_cost, 2);

                    if ((float) $row->additional_cost > 0) {
                        return $total . '<br><small>' . number_format($row->purchase_cost, 2) . ' + ' . number_format($row->additional_cost, 2) . '</small>';
                    }

                    return $total;
                })
                ->addColumn('warranty_badge', function ($row) {
                    if (! $row->warranty_expiry) {
                        return '<span class="badge bg-secondary">None</span>';
                    }

                    $label = $row->warranty_expiry->format('d M, Y');
                    $class = $row->is_under_warranty ? 'bg-success' : 'bg-danger';
                    $text = $row->is_under_warranty ? 'Under Warranty' : 'Expired';

                    return '<span class="badge ' . $class . '">' . $text . '</span><br><small>' . $label . '</small>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.asset-purchases.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'asset_name', 'source_document', 'total_cost_formatted', 'warranty_badge', 'action'])
                ->make(true);
        }

        $vendors = Vendor::active()->orderBy('name')->get();

        return view('admin.asset-purchases.index', compact('vendors'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.asset-purchases.create', $this->formData());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AssetPurchaseRequest $request)
    {
        DB::beginTransaction();

        try {
            $assetPurchase = $this->assetPurchaseService->create($request->validated());
            $assetPurchase->load('asset');

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'asset-purchases',
                'action' => 'create',
                'model' => 'AssetPurchase',
                'model_id' => $assetPurchase->id,
                'description' => 'Purchase information recorded for asset "' . ($assetPurchase->asset->asset_code ?? $assetPurchase->asset_id) . '"',
                'new_data' => $assetPurchase->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Asset purchase information saved successfully.'
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
    public function edit(AssetPurchase $assetPurchase)
    {
        return view('admin.asset-purchases.edit', array_merge(
            $this->formData($assetPurchase),
            ['assetPurchase' => $assetPurchase]
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AssetPurchaseRequest $request, AssetPurchase $assetPurchase)
    {
        DB::beginTransaction();

        try {
            $oldData = $assetPurchase->toArray();
            $updated = $this->assetPurchaseService->update($assetPurchase, $request->validated());
            $updated->load('asset');

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'asset-purchases',
                'action' => 'update',
                'model' => 'AssetPurchase',
                'model_id' => $assetPurchase->id,
                'description' => 'Purchase information updated for asset "' . ($updated->asset->asset_code ?? $updated->asset_id) . '"',
                'new_data' => $updated->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.asset-purchases.index'),
                'message' => 'Asset purchase information updated successfully.'
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
    public function destroy(AssetPurchase $assetPurchase)
    {
        DB::beginTransaction();

        try {
            $oldData = $assetPurchase->toArray();

            $this->assetPurchaseService->delete($assetPurchase);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'asset-purchases',
                'action' => 'delete',
                'model' => 'AssetPurchase',
                'model_id' => $oldData['id'],
                'description' => 'Purchase information deleted for asset id ' . $oldData['asset_id'],
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Asset purchase information deleted successfully.'
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

        $model = AssetPurchase::find($id);
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

    /**
     * Dropdown data shared by create/edit. Assets that already have a
     * purchase record are excluded so the UI cannot offer a choice that
     * would fail the one-record-per-asset rule; on edit, this record's own
     * asset is added back so the current selection is never lost — the
     * same technique `DeliveryNoteController` established for its own
     * one-to-one relationship.
     */
    protected function formData(?AssetPurchase $assetPurchase = null): array
    {
        $assets = Asset::active()
            ->whereDoesntHave('assetPurchase')
            ->when($assetPurchase, fn ($q) => $q->orWhere('id', $assetPurchase->asset_id))
            ->orderBy('asset_code')
            ->get();

        return [
            'assets' => $assets,
            'vendors' => Vendor::active()->orderBy('name')->get(),
            'purchaseOrders' => PurchaseOrder::active()->orderBy('id', 'DESC')->get(),
        ];
    }
}
