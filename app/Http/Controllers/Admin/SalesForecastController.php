<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Opportunity;
use Illuminate\Http\Request;

class SalesForecastController extends Controller
{
    /**
     * Display the Sales Forecasting report.
     */
    public function index(Request $request)
    {
        $openOpportunities = Opportunity::active()
            ->whereNotIn('stage', ['won', 'lost'])
            ->get();

        $totalPipelineValue = $openOpportunities->sum('amount');
        $weightedForecastValue = $openOpportunities->sum(function ($opportunity) {
            return $opportunity->amount * (($opportunity->probability ?? 0) / 100);
        });

        $wonThisMonth = Opportunity::active()
            ->where('stage', 'won')
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->sum('amount');

        $byStage = Opportunity::active()
            ->selectRaw('stage, COUNT(*) as total, SUM(amount) as amount')
            ->groupBy('stage')
            ->get();

        $byMonth = $openOpportunities
            ->filter(fn($opportunity) => $opportunity->expected_close_date)
            ->groupBy(fn($opportunity) => $opportunity->expected_close_date->format('M Y'))
            ->map(function ($group) {
                return (object) [
                    'total' => $group->count(),
                    'amount' => $group->sum('amount'),
                    'weighted' => $group->sum(fn($opportunity) => $opportunity->amount * (($opportunity->probability ?? 0) / 100)),
                ];
            });

        return view('admin.sales-forecast.index', compact(
            'totalPipelineValue',
            'weightedForecastValue',
            'wonThisMonth',
            'byStage',
            'byMonth'
        ));
    }
}
