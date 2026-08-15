<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\PosSale;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\SalesQuotation;
use App\Models\SalesReturn;
use Illuminate\Http\Request;

class SalesReportController extends Controller
{
    /**
     * Display the Sales reporting dashboard.
     */
    public function index(Request $request)
    {
        $totalQuotations = SalesQuotation::count();
        $totalOrders = SalesOrder::count();
        $totalInvoices = SalesInvoice::count();

        $acceptedQuotations = SalesQuotation::where('quotation_status', 'accepted')->count();
        $conversionRate = $totalQuotations > 0 ? round(($acceptedQuotations / $totalQuotations) * 100) : 0;

        $totalRevenue = SalesInvoice::where('invoice_status', '!=', 'cancelled')->sum('grand_total');

        $outstandingBalance = SalesInvoice::whereIn('invoice_status', ['sent', 'partially_paid', 'overdue'])
            ->get()
            ->sum('balance_due');

        $totalPosSales = PosSale::where('pos_status', 'completed')->sum('grand_total');

        $pendingReturns = SalesReturn::whereIn('return_status', ['pending', 'received', 'inspected'])->count();

        $ordersByStatus = SalesOrder::selectRaw('order_status, count(*) as total')
            ->groupBy('order_status')
            ->pluck('total', 'order_status');

        $invoicesByStatus = SalesInvoice::selectRaw('invoice_status, count(*) as total')
            ->groupBy('invoice_status')
            ->pluck('total', 'invoice_status');

        $topSalespersons = SalesOrder::selectRaw('assigned_to, sum(grand_total) as total_sales, count(*) as order_count')
            ->whereNotNull('assigned_to')
            ->where('order_status', '!=', 'cancelled')
            ->groupBy('assigned_to')
            ->orderByDesc('total_sales')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                $row->admin = Admin::find($row->assigned_to);
                return $row;
            });

        return view('admin.sales-reports.index', compact(
            'totalQuotations',
            'totalOrders',
            'totalInvoices',
            'conversionRate',
            'totalRevenue',
            'outstandingBalance',
            'totalPosSales',
            'pendingReturns',
            'ordersByStatus',
            'invoicesByStatus',
            'topSalespersons'
        ));
    }
}
