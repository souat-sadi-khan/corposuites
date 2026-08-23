<?php

namespace App\Traits;

/**
 * Shared "clean machine-readable CSV" helper (PART 11's own CSV
 * Requirements: "stable column names", "avoid decorative content that
 * makes CSV processing difficult") — reused by both AttendancePortal
 * Controller and AttendanceReportController so the actual CSV-writing
 * (fputcsv streaming, filename/headers) exists in exactly one place,
 * matching this project's existing App\Traits\ActivityLogger /
 * App\Traits\ExportsHtmlSpreadsheet convention for cross-controller
 * helpers. Mirrors the plain fputcsv-streaming approach
 * EmployeeController::export() and HrmDetailExportController::csv()
 * already use elsewhere in this codebase — no new dependency, and
 * consistent with an existing pattern rather than a third one.
 */
trait ExportsCsv
{
    /**
     * @param  iterable<array<int, scalar|null>>  $rows
     */
    protected function csvResponse(string $filenameBase, array $headers, iterable $rows)
    {
        $filename = $filenameBase . '_' . now()->format('Y_m_d_His') . '.csv';

        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);

            foreach ($rows as $row) {
                fputcsv($out, $row);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
