<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssetDisposalRequest;
use App\Models\Admin;
use App\Models\Asset;
use App\Models\AssetDisposal;
use App\Services\AssetDisposalService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class AssetDisposalController extends Controller
{
    use ActivityLogger;

    protected $disposalService;

    public function __construct(AssetDisposalService $disposalService)
    {
        $this->disposalService = $disposalService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = AssetDisposal::query()->with(['asset', 'approvedBy']);

            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            if ($request->disposal_status) {
                $query->where('disposal_status', $request->disposal_status);
            }

            if ($request->disposal_method) {
                $query->where('disposal_method', $request->disposal_method);
            }

            // Gain / loss is a stored figure, so this is a plain comparison.
            if ($request->outcome === 'gain') {
                $query->where('gain_loss', '>', 0);
            } elseif ($request->outcome === 'loss') {
                $query->where('gain_loss', '<', 0);
            }

            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('recipient', 'like', "%{$search}%")
                        ->orWhereHas('asset', function ($sub) use ($search) {
                            $sub->where('name', 'like', "%{$search}%")
                                ->orWhere('asset_code', 'like', "%{$search}%");
                        });
                });
            }

            $query->orderBy('disposal_date', 'DESC')->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.asset-disposals.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('asset_name', function ($row) {
                    if (! $row->asset) {
                        return '<span class="text-danger">Asset removed</span>';
                    }

                    return '<b class="tl-name-txt">' . e($row->asset->name) . '</b><br><small>' . e($row->asset->asset_code) . '</small>';
                })
                ->addColumn('method_col', function ($row) {
                    $recipient = $row->recipient ? '<br><small>' . e($row->recipient) . '</small>' : '';

                    return '<span class="badge bg-secondary">' . e($row->method_label) . '</span>' . $recipient;
                })
                ->addColumn('disposal_date_formatted', function ($row) {
                    return $row->disposal_date->format('d M, Y');
                })
                ->addColumn('proceeds_col', function ($row) {
                    return number_format($row->proceeds, 2) . '<br><small>BV ' . number_format($row->book_value_at_disposal, 2) . '</small>';
                })
                ->addColumn('gain_loss_col', function ($row) {
                    $value = (float) $row->gain_loss;

                    if (abs($value) < 0.005) {
                        return '<span class="text-muted">0.00</span>';
                    }

                    $class = $value > 0 ? 'text-success' : 'text-danger';
                    $label = $value > 0 ? 'Gain' : 'Loss';

                    return '<span class="' . $class . '">' . number_format(abs($value), 2) . '</span><br><small class="' . $class . '">' . $label . '</small>';
                })
                ->addColumn('disposal_status_badge', function ($row) {
                    $map = [
                        'pending' => 'bg-warning',
                        'completed' => 'bg-success',
                        'cancelled' => 'bg-danger',
                    ];
                    $class = $map[$row->disposal_status] ?? 'bg-secondary';

                    return '<span class="badge ' . $class . '">' . ucfirst($row->disposal_status) . '</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.asset-disposals.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'asset_name', 'method_col', 'proceeds_col', 'gain_loss_col', 'disposal_status_badge', 'action'])
                ->make(true);
        }

        return view('admin.asset-disposals.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.asset-disposals.create', $this->formData());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AssetDisposalRequest $request)
    {
        DB::beginTransaction();

        try {
            $disposal = $this->disposalService->create($request->validated());
            $disposal->load('asset');

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'asset-disposals',
                'action' => 'create',
                'model' => 'AssetDisposal',
                'model_id' => $disposal->id,
                'description' => 'Asset "' . ($disposal->asset->asset_code ?? $disposal->asset_id) . '" disposed (' . $disposal->method_label . ')',
                'new_data' => $disposal->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Disposal recorded successfully.'
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
    public function edit(AssetDisposal $assetDisposal)
    {
        return view('admin.asset-disposals.edit', array_merge(
            $this->formData($assetDisposal),
            ['disposal' => $assetDisposal]
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AssetDisposalRequest $request, AssetDisposal $assetDisposal)
    {
        DB::beginTransaction();

        try {
            $oldData = $assetDisposal->toArray();
            $updated = $this->disposalService->update($assetDisposal, $request->validated());
            $updated->load('asset');

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'asset-disposals',
                'action' => 'update',
                'model' => 'AssetDisposal',
                'model_id' => $assetDisposal->id,
                'description' => 'Disposal updated for asset "' . ($updated->asset->asset_code ?? $updated->asset_id) . '" (' . $updated->disposal_status . ')',
                'new_data' => $updated->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.asset-disposals.index'),
                'message' => 'Disposal updated successfully.'
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
    public function destroy(AssetDisposal $assetDisposal)
    {
        DB::beginTransaction();

        try {
            $oldData = $assetDisposal->toArray();

            $this->disposalService->delete($assetDisposal);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'asset-disposals',
                'action' => 'delete',
                'model' => 'AssetDisposal',
                'model_id' => $oldData['id'],
                'description' => 'Disposal deleted for asset id ' . $oldData['asset_id'],
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Disposal deleted successfully.'
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

        $model = AssetDisposal::find($id);
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
     * disposal record are excluded, with the edited record's own asset
     * added back so the selection is never lost — the same picker
     * technique `AssetPurchaseController` uses for its own one-to-one.
     */
    protected function formData(?AssetDisposal $disposal = null): array
    {
        $assets = Asset::active()
            ->whereDoesntHave('assetDisposal')
            ->when($disposal, fn ($q) => $q->orWhere('id', $disposal->asset_id))
            ->orderBy('asset_code')
            ->get();

        return [
            'assets' => $assets,
            'admins' => Admin::orderBy('name')->get(),
        ];
    }
}
