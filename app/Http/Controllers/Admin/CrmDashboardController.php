<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Company;
use App\Models\Contact;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Quotation;
use Illuminate\Http\Request;

class CrmDashboardController extends Controller
{
    /**
     * Display the CRM dashboard.
     */
    public function index(Request $request)
    {
        $totalLeads = Lead::count();
        $activeLeads = Lead::active()->count();

        $leadsBySource = Lead::active()
            ->with('leadSource')
            ->get()
            ->groupBy(fn($lead) => $lead->leadSource->name ?? 'Unassigned')
            ->map->count()
            ->sortDesc();

        $leadsByStage = Lead::active()
            ->with('leadStatus')
            ->get()
            ->groupBy(fn($lead) => $lead->leadStatus->name ?? 'Unassigned')
            ->map->count();

        $totalContacts = Contact::active()->count();
        $totalCompanies = Company::active()->count();

        $openOpportunities = Opportunity::active()->whereNotIn('stage', ['won', 'lost'])->get();
        $openOpportunityCount = $openOpportunities->count();
        $openPipelineValue = $openOpportunities->sum('amount');

        $wonThisMonth = Opportunity::active()
            ->where('stage', 'won')
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->count();

        $lostThisMonth = Opportunity::active()
            ->where('stage', 'lost')
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->count();

        $pendingActivities = Activity::active()->where('activity_status', 'pending')->count();
        $overdueActivities = Activity::active()
            ->where('activity_status', 'pending')
            ->where('due_date', '<', now())
            ->count();

        $upcomingFollowUps = FollowUp::active()
            ->where('is_completed', false)
            ->where('remind_at', '>=', now())
            ->orderBy('remind_at')
            ->limit(5)
            ->get();

        $quotationsByStatus = Quotation::active()
            ->selectRaw('quotation_status, COUNT(*) as total, SUM(amount) as amount')
            ->groupBy('quotation_status')
            ->get();

        return view('admin.crm-dashboard.index', compact(
            'totalLeads',
            'activeLeads',
            'leadsBySource',
            'leadsByStage',
            'totalContacts',
            'totalCompanies',
            'openOpportunityCount',
            'openPipelineValue',
            'wonThisMonth',
            'lostThisMonth',
            'pendingActivities',
            'overdueActivities',
            'upcomingFollowUps',
            'quotationsByStatus'
        ));
    }
}
