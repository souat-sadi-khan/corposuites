<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LeaveReportController extends Controller
{
    /**
     * Leave analytics dashboard (Phase F3): balance summary, utilization by type,
     * monthly trend, and status breakdown. Read-only, mirrors HrReportController.
     */
    public function index(Request $request)
    {
        $year = (int) $request->input('year', now()->year);

        // Year options: distinct balance years plus the current year.
        $years = LeaveBalance::query()
            ->select('year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->push(now()->year)
            ->unique()
            ->sortDesc()
            ->values();

        // --- Headline stats ----------------------------------------------------
        $totalEmployees = Employee::active()->count();

        $pendingRequests = LeaveRequest::where('approval_status', 'pending')->count();

        $approvedThisYear = LeaveRequest::where('approval_status', 'approved')
            ->whereYear('start_date', $year)
            ->count();

        $daysTakenThisYear = (float) LeaveRequest::where('approval_status', 'approved')
            ->whereYear('start_date', $year)
            ->sum('total_days');

        // --- Utilization by leave type (allocated vs used) ---------------------
        $balanceByType = LeaveBalance::where('year', $year)
            ->selectRaw('leave_type_id, SUM(allocated_days) as allocated, SUM(used_days) as used, SUM(carried_days) as carried')
            ->groupBy('leave_type_id')
            ->get()
            ->keyBy('leave_type_id');

        $leaveTypes = LeaveType::orderBy('name')->get();

        $utilization = $leaveTypes->map(function ($type) use ($balanceByType) {
            $row = $balanceByType->get($type->id);
            $allocated = (float) ($row->allocated ?? 0);
            $used = (float) ($row->used ?? 0);

            return (object) [
                'name' => $type->name,
                'allocated' => $allocated,
                'used' => $used,
                'remaining' => round($allocated - $used, 2),
                'carried' => (float) ($row->carried ?? 0),
                'utilization_pct' => $allocated > 0 ? round(($used / $allocated) * 100, 1) : 0.0,
            ];
        });

        // --- Monthly trend of approved leave days (by start month) -------------
        $monthlyRaw = LeaveRequest::where('approval_status', 'approved')
            ->whereYear('start_date', $year)
            ->selectRaw('MONTH(start_date) as m, SUM(total_days) as days')
            ->groupBy('m')
            ->pluck('days', 'm');

        $monthlyTrend = collect(range(1, 12))->map(function ($m) use ($monthlyRaw) {
            return (object) [
                'label' => Carbon::create()->month($m)->format('M'),
                'days' => (float) ($monthlyRaw[$m] ?? 0),
            ];
        });

        // --- Status breakdown for the year ------------------------------------
        $statusBreakdown = LeaveRequest::whereYear('start_date', $year)
            ->selectRaw('approval_status, COUNT(*) as total')
            ->groupBy('approval_status')
            ->pluck('total', 'approval_status');

        // --- Top leave takers (approved days) ---------------------------------
        $topTakers = LeaveRequest::where('approval_status', 'approved')
            ->whereYear('start_date', $year)
            ->with('employee')
            ->selectRaw('employee_id, SUM(total_days) as days, COUNT(*) as requests')
            ->groupBy('employee_id')
            ->orderByDesc('days')
            ->limit(10)
            ->get();

        return view('admin.leave-reports.index', compact(
            'year',
            'years',
            'totalEmployees',
            'pendingRequests',
            'approvedThisYear',
            'daysTakenThisYear',
            'utilization',
            'monthlyTrend',
            'statusBreakdown',
            'topTakers'
        ));
    }
}
