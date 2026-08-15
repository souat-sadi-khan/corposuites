<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AssetCategory;
use App\Models\AssetDisposal;
use App\Models\AssetMaintenanceRecord;
use App\Models\AssetMaintenanceSchedule;
use App\Services\AssetDisposalService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AssetReportController extends Controller
{
    protected $disposalService;

    public function __construct(AssetDisposalService $disposalService)
    {
        $this->disposalService = $disposalService;
    }

    /**
     * Asset Reports — the module's landing dashboard: the headline figures
     * from every Asset Management screen on one page, plus a directory
     * linking through to each.
     *
     * Pure read-only report: no new table/Model/Service/Request, the same
     * closing-module role Financial Reports, Sales Reports, Purchase
     * Reports and Inventory Reports play for their own sections.
     *
     * One deliberate exception to this project's "each module computes its
     * own aggregation" rule: net book value is taken from
     * `AssetDisposalService::bookValueAt()`, which was made public in the
     * previous module specifically so this one would not become a third
     * copy of the depreciation convention. Everything else here is counted
     * locally.
     */
    public function index(Request $request)
    {
        $categoryId = $request->asset_category_id;
        $asOfDate = $request->as_of_date ? Carbon::parse($request->as_of_date) : Carbon::now();

        $categories = AssetCategory::active()->orderBy('name')->get();

        $assets = Asset::with(['assetCategory', 'assetPurchase'])
            ->where('status', true)
            ->when($categoryId, fn ($q) => $q->where('asset_category_id', $categoryId))
            ->get();

        $live = $assets->where('asset_status', '!=', 'disposed');

        $totals = [
            'assets' => $assets->count(),
            'in_use' => $assets->where('asset_status', 'in_use')->count(),
            'in_store' => $assets->where('asset_status', 'in_store')->count(),
            'under_maintenance' => $assets->where('asset_status', 'under_maintenance')->count(),
            'disposed' => $assets->where('asset_status', 'disposed')->count(),
            'cost' => round($assets->sum(fn ($a) => (float) ($a->assetPurchase->total_cost ?? 0)), 2),
            'missing_purchase' => $assets->filter(fn ($a) => $a->assetPurchase === null)->count(),
        ];

        // Book value is only meaningful for assets still on the books.
        $bookValue = round(
            $live->sum(fn ($asset) => $this->disposalService->bookValueAt($asset->id, $asOfDate->toDateString())),
            2
        );
        $liveCost = round($live->sum(fn ($a) => (float) ($a->assetPurchase->total_cost ?? 0)), 2);

        $totals['book_value'] = $bookValue;
        $totals['accumulated_depreciation'] = round(max(0, $liveCost - $bookValue), 2);

        $assetIds = $assets->pluck('id');

        return view('admin.asset-reports.index', [
            'categories' => $categories,
            'categoryId' => $categoryId,
            'asOfDate' => $asOfDate->toDateString(),
            'totals' => $totals,
            'byCategory' => $this->byCategory($assets),
            'byState' => $this->byState($assets),
            'assignments' => $this->assignmentSummary($assetIds),
            'maintenance' => $this->maintenanceSummary($assetIds),
            'overdueMaintenance' => $this->overdueMaintenance($assetIds),
            'disposals' => $this->disposalSummary($assetIds),
        ]);
    }

    protected function byCategory(Collection $assets): Collection
    {
        return $assets
            ->groupBy(fn ($asset) => $asset->assetCategory->name ?? 'Uncategorised')
            ->map->count()
            ->sortDesc();
    }

    protected function byState(Collection $assets): Collection
    {
        return $assets
            ->groupBy(fn ($asset) => ucwords(str_replace('_', ' ', $asset->asset_status)))
            ->map->count()
            ->sortDesc();
    }

    /**
     * Custody position across the assets in scope.
     */
    protected function assignmentSummary(Collection $assetIds): array
    {
        $open = AssetAssignment::with('employee')
            ->whereIn('asset_id', $assetIds)
            ->where('assignment_status', 'assigned')
            ->get();

        return [
            'out' => $open->count(),
            'holders' => $open->pluck('employee_id')->unique()->count(),
            'overdue' => $open->filter(fn ($a) => $a->is_overdue)->count(),
        ];
    }

    /**
     * Maintenance workload and spend across the assets in scope.
     */
    protected function maintenanceSummary(Collection $assetIds): array
    {
        $schedules = AssetMaintenanceSchedule::whereIn('asset_id', $assetIds)
            ->where('schedule_status', 'active')
            ->get();

        $records = AssetMaintenanceRecord::whereIn('asset_id', $assetIds)
            ->where('record_status', 'completed')
            ->get();

        return [
            'active_schedules' => $schedules->count(),
            'overdue' => $schedules->filter(fn ($s) => $s->is_overdue)->count(),
            'due_soon' => $schedules->filter(
                fn ($s) => ! $s->is_overdue && $s->is_due === false && $s->days_until_due !== null && $s->days_until_due <= 30
            )->count(),
            'jobs_done' => $records->count(),
            'total_spend' => round($records->sum(fn ($r) => (float) $r->cost), 2),
            'total_downtime' => round($records->sum(fn ($r) => (float) $r->downtime_hours), 2),
        ];
    }

    /**
     * The schedules actually running late — the actionable list, capped so
     * the dashboard stays a dashboard.
     */
    protected function overdueMaintenance(Collection $assetIds): Collection
    {
        return AssetMaintenanceSchedule::with('asset')
            ->whereIn('asset_id', $assetIds)
            ->where('schedule_status', 'active')
            ->whereNotNull('next_due_date')
            ->whereDate('next_due_date', '<', now()->toDateString())
            ->orderBy('next_due_date', 'ASC')
            ->limit(10)
            ->get();
    }

    /**
     * Realised outcome on assets already disposed of.
     */
    protected function disposalSummary(Collection $assetIds): array
    {
        $disposals = AssetDisposal::whereIn('asset_id', $assetIds)
            ->where('disposal_status', 'completed')
            ->get();

        return [
            'count' => $disposals->count(),
            'proceeds' => round($disposals->sum(fn ($d) => (float) $d->proceeds), 2),
            'book_value' => round($disposals->sum(fn ($d) => (float) $d->book_value_at_disposal), 2),
            'net' => round($disposals->sum(fn ($d) => (float) $d->gain_loss), 2),
            'gains' => $disposals->filter(fn ($d) => (float) $d->gain_loss > 0)->count(),
            'losses' => $disposals->filter(fn ($d) => (float) $d->gain_loss < 0)->count(),
        ];
    }
}
