<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DebitNote;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\PurchaseReturn;
use App\Models\Vendor;
use App\Models\VendorPerformanceReview;
use Illuminate\Http\Request;

class PurchaseReportController extends Controller
{
    /**
     * Display the Purchase reporting dashboard.
     */
    public function index(Request $request)
    {
        $totalVendors = Vendor::count();
        $activeVendors = Vendor::active()->count();

        $totalRequests = PurchaseRequest::count();
        $approvedRequests = PurchaseRequest::where('request_status', 'approved')->count();
        $approvalRate = $totalRequests > 0 ? round(($approvedRequests / $totalRequests) * 100) : 0;

        $totalOrders = PurchaseOrder::count();
        $totalSpend = PurchaseOrder::where('order_status', '!=', 'cancelled')->sum('grand_total');

        $totalInvoices = PurchaseInvoice::count();
        $outstandingBalance = PurchaseInvoice::whereIn('invoice_status', ['pending', 'approved', 'disputed'])
            ->get()
            ->sum('balance_due');

        $discrepancyInvoices = PurchaseInvoice::where('match_status', 'discrepancy')->count();

        $pendingReturns = PurchaseReturn::whereIn('return_status', ['pending', 'approved', 'shipped'])->count();
        $totalDebitAmount = DebitNote::where('debit_status', '!=', 'cancelled')->sum('grand_total');

        $ordersByStatus = PurchaseOrder::selectRaw('order_status, count(*) as total')
            ->groupBy('order_status')
            ->pluck('total', 'order_status');

        $invoicesByMatchStatus = PurchaseInvoice::selectRaw('match_status, count(*) as total')
            ->groupBy('match_status')
            ->pluck('total', 'match_status');

        $topVendors = PurchaseOrder::selectRaw('vendor_id, sum(grand_total) as total_spend, count(*) as order_count')
            ->where('order_status', '!=', 'cancelled')
            ->groupBy('vendor_id')
            ->orderByDesc('total_spend')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                $row->vendor = Vendor::find($row->vendor_id);
                return $row;
            });

        $vendorRatings = VendorPerformanceReview::selectRaw('vendor_id, avg(overall_rating) as avg_rating')
            ->groupBy('vendor_id')
            ->pluck('avg_rating', 'vendor_id');

        return view('admin.purchase-reports.index', compact(
            'totalVendors',
            'activeVendors',
            'totalRequests',
            'approvalRate',
            'totalOrders',
            'totalSpend',
            'totalInvoices',
            'outstandingBalance',
            'discrepancyInvoices',
            'pendingReturns',
            'totalDebitAmount',
            'ordersByStatus',
            'invoicesByMatchStatus',
            'topVendors',
            'vendorRatings'
        ));
    }
}
