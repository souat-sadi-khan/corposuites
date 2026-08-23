<?php

namespace App\Traits;

/**
 * "Excel Export" without a Composer package. Checked first (per this
 * project's own established habit — the same check already done before
 * building the PDF export in Module 9): no maatwebsite/excel or any other
 * spreadsheet library is installed, but App\Http\Controllers\Admin\
 * HrmDetailExportController::csv() already establishes a lightweight
 * "excel" output mode — a plain-value export whose Content-Type is tagged
 * application/vnd.ms-excel so double-clicking it opens Excel by default.
 *
 * That existing convention keeps its OWN data flat (no colors/badges,
 * same content as a CSV, just re-tagged) — fine for a simple list, but the
 * Attendance Report/Monthly Sheet exports need to actually LOOK like the
 * on-screen views (per the same "same design as the views" requirement
 * already satisfied for the PDF export), which plain values can't carry.
 *
 * Excel (and every other real spreadsheet app) has always been able to
 * import a plain HTML <table> directly — this is the same "legacy Web
 * Page" .xls format Excel itself has offered as a Save-As option for
 * decades. Serving real HTML (a <table> with inline background/text
 * colors, bold, borders — exactly the same badge/summary styling the
 * screen and PDF already use) with an .xls filename and the
 * application/vnd.ms-excel Content-Type opens directly in Excel as a
 * genuinely styled spreadsheet, with zero new dependency — the same
 * "reuse the target application's own native import, don't add a library"
 * principle the PDF export already uses via the browser's print engine.
 *
 * A one-time "this isn't the exact file type it claims to be" compatibility
 * prompt from Excel is expected and accepted here (the exact same prompt
 * every "Web Page" .xls export from Excel's own File > Save As has always
 * produced) — clicking Yes opens the file normally.
 */
trait ExportsHtmlSpreadsheet
{
    protected function htmlSpreadsheetResponse(string $view, array $data, string $filenameBase)
    {
        $html = view($view, $data)->render();
        $filename = $filenameBase . '_' . now()->format('Y_m_d_His') . '.xls';

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
