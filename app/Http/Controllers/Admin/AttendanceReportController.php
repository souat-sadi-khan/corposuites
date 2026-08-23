<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AttendanceReportService;
use App\Traits\ExportsCsv;
use App\Traits\ExportsHtmlSpreadsheet;
use Illuminate\Http\Request;

/**
 * Dedicated admin "Attendance Report" — advanced multi-employee filtering
 * (department/designation/shift/employee type/employment status/employee)
 * plus organization-wide summary cards, distinct from both the generic HR
 * dashboard tile (App\Http\Controllers\Admin\HrReportController, a single
 * today/this-month snapshot) and the day-by-day Monthly Attendance Sheet
 * (AttendancePortalController::monthly(), which now shares this SAME
 * advanced-search filter set via AttendanceReportService). Kept as its own
 * thin controller (PART 16's suggested architecture) rather than growing
 * AttendanceController — all the actual calculation/filtering still runs
 * through the one shared AttendanceReportService, never re-derived here.
 */
class AttendanceReportController extends Controller
{
    use ExportsHtmlSpreadsheet, ExportsCsv;

    public function __construct(private AttendanceReportService $reportService)
    {
    }

    public function index(Request $request)
    {
        return view('admin.attendances.report', $this->buildReportData($request));
    }

    /**
     * PART 11's PDF export — deliberately NOT a server-side PDF library
     * (checked first: this project already has an established, documented
     * convention for exactly this — the reusable <x-print-document> Blade
     * component, used by Delivery Notes/Barcode Generator/POS
     * Receipts/Salary Certificate, whose own doc comment explicitly says
     * this is "rather than adding a server-side PDF library dependency".
     * Reusing it here keeps this export visually/architecturally consistent
     * with every other document in the app instead of introducing a second,
     * competing PDF pipeline). Runs through the EXACT SAME
     * buildReportData() as the browser view, so the export can never drift
     * from — or accidentally ignore — whatever filters are currently
     * applied.
     */
    public function exportPdf(Request $request)
    {
        $data = $this->buildReportData($request);
        $data['filterSummary'] = $this->reportService->filterSummary($request, $data['filters']);

        return view('admin.attendances.report-pdf', $data);
    }

    /**
     * "Excel" export — see App\Traits\ExportsHtmlSpreadsheet's own doc
     * comment for why this is a styled HTML table served as .xls (no
     * maatwebsite/excel or any other new dependency), reproducing the same
     * summary cards + table this page shows on screen. Same
     * buildReportData() pipeline as the browser view and the PDF export.
     */
    public function exportExcel(Request $request)
    {
        $data = $this->buildReportData($request);
        $data['filterSummary'] = $this->reportService->filterSummary($request, $data['filters']);

        return $this->htmlSpreadsheetResponse('admin.attendances.report-excel', $data, 'attendance_report');
    }

    /**
     * PART 11's "clean machine-readable CSV" — one row per employee,
     * matching the exact columns already shown in the on-screen table, in a
     * fixed order that never changes regardless of which filters are
     * applied (PART 11's own "use stable column names" requirement). No
     * decorative rows (no company header/legend/summary block the way the
     * PDF/Excel exports have) — just the data, per "avoid decorative
     * content that makes CSV processing difficult". Same buildReportData()
     * pipeline as every other output format.
     */
    public function exportCsv(Request $request)
    {
        $data = $this->buildReportData($request);
        $employees = $data['employees'];
        $reports = $data['reports'];

        $headers = [
            'Employee ID', 'Employee Code', 'Employee Name', 'Department', 'Designation',
            'Present', 'Absent', 'Late', 'Half Day', 'Leave',
            'Worked Hours', 'Overtime', 'Missing Checkout',
        ];

        $rows = $employees->map(function ($employee) use ($reports) {
            $summary = $reports[$employee->id]['summary'] ?? null;

            return [
                $employee->id,
                $employee->employee_code,
                $employee->full_name,
                $employee->department?->name ?? '',
                $employee->designation?->name ?? '',
                $summary['present'] ?? 0,
                $summary['absent'] ?? 0,
                $summary['late'] ?? 0,
                $summary['half_day'] ?? 0,
                $summary['on_leave'] ?? 0,
                $summary['worked_label'] ?? '--',
                $summary['overtime_label'] ?? '--',
                $summary['missing_checkouts'] ?? 0,
            ];
        });

        return $this->csvResponse('attendance_report', $headers, $rows);
    }

    /**
     * The one shared pipeline every output format (browser view, PDF,
     * Excel, and CSV) reads from — PART 11's own rule ("Use the same
     * shared AttendanceReportService/query builder for Browser View, PDF,
     * Excel, CSV so all outputs remain consistent").
     */
    private function buildReportData(Request $request): array
    {
        [$from, $to, $month] = $this->reportService->resolveRange($request);

        $employees = $this->reportService->filteredEmployeesQuery($request)->get();

        $reports = $employees->isNotEmpty()
            ? $this->reportService->buildForEmployees($employees, $from, $to)
            : [];

        [$employees, $reports] = $this->reportService->narrowToActivityFilters($employees, $reports, $request);

        $totals = $this->reportService->aggregateTotals($reports);
        $filters = $this->reportService->filterOptions();

        return compact('from', 'to', 'month', 'employees', 'reports', 'totals', 'filters');
    }
}
