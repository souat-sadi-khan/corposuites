<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\EmailCommunication;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Quotation;
use Illuminate\Http\Request;

class CrmReportController extends Controller
{
    /**
     * Display the CRM reporting dashboard.
     */
    public function index(Request $request)
    {
        $totalLeads = Lead::count();
        $convertedLeads = Lead::whereHas('leadStatus', function ($q) {
            $q->where('name', 'like', '%won%')->orWhere('name', 'like', '%qualified%')->orWhere('name', 'like', '%converted%');
        })->count();
        $leadConversionRate = $totalLeads > 0 ? round(($convertedLeads / $totalLeads) * 100, 1) : 0;

        $leadsBySourceMonthly = Lead::active()
            ->with('leadSource')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->get()
            ->groupBy(fn($lead) => $lead->leadSource->name ?? 'Unassigned')
            ->map->count();

        $opportunitiesByStage = Opportunity::active()
            ->selectRaw('stage, COUNT(*) as total, SUM(amount) as amount')
            ->groupBy('stage')
            ->get();

        $winRate = Opportunity::active()->whereIn('stage', ['won', 'lost'])->count();
        $wonCount = Opportunity::active()->where('stage', 'won')->count();
        $opportunityWinRate = $winRate > 0 ? round(($wonCount / $winRate) * 100, 1) : 0;

        $activityCompletionRate = Activity::active()->count() > 0
            ? round((Activity::active()->where('activity_status', 'completed')->count() / Activity::active()->count()) * 100, 1)
            : 0;

        $quotationConversion = Quotation::active()->count() > 0
            ? round((Quotation::active()->where('quotation_status', 'accepted')->count() / Quotation::active()->count()) * 100, 1)
            : 0;

        $emailVolumeByDirection = EmailCommunication::active()
            ->selectRaw('direction, COUNT(*) as total')
            ->groupBy('direction')
            ->pluck('total', 'direction');

        return view('admin.crm-reports.index', compact(
            'totalLeads',
            'convertedLeads',
            'leadConversionRate',
            'leadsBySourceMonthly',
            'opportunitiesByStage',
            'opportunityWinRate',
            'activityCompletionRate',
            'quotationConversion',
            'emailVolumeByDirection'
        ));
    }
}
