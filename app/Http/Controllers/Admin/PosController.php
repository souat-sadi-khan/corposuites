<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PosSaleCheckoutRequest;
use App\Models\Customer;
use App\Models\PosSale;
use App\Models\Product;
use App\Services\PosSaleService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class PosController extends Controller
{
    use ActivityLogger;

    protected $posSaleService;

    public function __construct(PosSaleService $posSaleService)
    {
        $this->posSaleService = $posSaleService;
    }

    /**
     * Sales history listing (read-only + void/receipt actions — a completed sale's items are never edited).
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = PosSale::query()->with(['customer', 'cashier'])->withCount('items');

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by POS status
            if ($request->pos_status) {
                $query->where('pos_status', $request->pos_status);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('pos_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($cq) use ($search) {
                            $cq->where('name', 'like', "%{$search}%");
                        });
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.pos.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('pos_number', function ($row) {
                    return '<b class="tl-name-txt">' . $row->pos_number . '</b><br><small>' . ($row->customer->name ?? 'Walk-in Customer') . '</small>';
                })
                ->addColumn('pos_status_badge', function ($row) {
                    $color = $row->pos_status === 'voided' ? 'danger' : 'success';
                    return '<span class="badge bg-' . $color . '">' . ucfirst($row->pos_status) . '</span>';
                })
                ->addColumn('payment_method_label', function ($row) {
                    return ucfirst(str_replace('_', ' ', $row->payment_method));
                })
                ->addColumn('grand_total_formatted', function ($row) {
                    return number_format($row->grand_total, 2);
                })
                ->addColumn('items_count_label', function ($row) {
                    return $row->items_count . ' item' . ($row->items_count == 1 ? '' : 's');
                })
                ->addColumn('sold_at_formatted', function ($row) {
                    return $row->sold_at ? $row->sold_at->format('d M, Y h:i A') : '-';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.pos.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'pos_number', 'pos_status_badge', 'action'])
                ->make(true);
        }

        return view('admin.pos.index');
    }

    /**
     * The live checkout terminal — product grid, cart, instant checkout.
     */
    public function terminal()
    {
        $customers = Customer::active()->get();
        $products = Product::active()->whereNotNull('selling_price')->get();

        return view('admin.pos.terminal', compact('customers', 'products'));
    }

    /**
     * Complete a sale from the terminal.
     */
    public function checkout(PosSaleCheckoutRequest $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->validated();
            $data['cashier_id'] = auth()->guard('admin')->id();
            $data['sold_at'] = now();
            $data['status'] = true;

            $posSale = $this->posSaleService->checkout($data);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'pos',
                'action' => 'checkout',
                'model' => 'PosSale',
                'model_id' => $posSale->id,
                'description' => 'POS Sale "' . $posSale->pos_number . '" completed',
                'new_data' => $posSale->load('items')->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Sale completed successfully.',
                'pos_sale_id' => $posSale->id,
                'receipt_url' => route('admin.pos.receipt', $posSale->id),
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
     * Void a completed sale (does not delete it — keeps the record for audit, marks it voided).
     */
    public function void(PosSale $posSale)
    {
        $oldData = $posSale->toArray();

        $this->posSaleService->void($posSale);

        $this->logActivity([
            'actor_type' => 'admin',
            'actor_id' => auth()->guard('admin')->id(),
            'module' => 'pos',
            'action' => 'void',
            'model' => 'PosSale',
            'model_id' => $posSale->id,
            'description' => 'POS Sale "' . $posSale->pos_number . '" voided',
            'new_data' => $posSale->fresh()->toArray(),
            'old_data' => $oldData
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Sale voided successfully.'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PosSale $posSale)
    {
        DB::beginTransaction();

        try {
            $oldData = $posSale->load('items')->toArray();

            $this->posSaleService->delete($posSale);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'pos',
                'action' => 'delete',
                'model' => 'PosSale',
                'model_id' => $oldData['id'],
                'description' => 'POS Sale "' . $oldData['pos_number'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'POS sale deleted successfully.'
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

        $model = PosSale::find($id);
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
     * Printable receipt, opened in a new tab.
     */
    public function receipt(PosSale $posSale)
    {
        $posSale->load('customer', 'cashier', 'items.product');

        return view('admin.pos.receipt', compact('posSale'));
    }
}
