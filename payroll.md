# Payroll Configuration & Settings — Requirement vs Current System

Source doc: `1-Payroll Configuration & Settings.docx`
Checked against: `app/Models/Payroll.php`, `PayrollItem.php`, `SalaryStructure.php`, `SalaryStructureItem.php`, `SalaryComponent.php`, `app/Services/PayrollService.php`, `SalaryStructureService.php`, `PayrollController.php`, `HrmSettingsController.php`, `Attendance.php`, migrations for `payrolls`, `salary_structures`, `salary_components`.

Legend: `[*]` = implemented in the system today. `[ ]` = not implemented / missing.

---

## Module 1: Multiple Salary Structure Templates (Daily, Monthly, Commission-based) — ✅ Done

- [*] Fixed monthly salary structure (`SalaryStructure.basic_salary` + earning/deduction items) — unchanged behavior
- [*] Daily pay-type salary structure (per-day rate × days actually worked, from Attendance, within the payroll period)
- [*] Commission-based salary structure (commission % × sales amount entered at payroll-generation time)
- [*] Pay Type selector per salary structure (Monthly / Daily / Commission-based), with the Basic Salary field's label/meaning switching automatically
- [*] Per-employee-group / bulk pay-type assignment — a reusable **Salary Template** (pay type + rate + components) can be created once and applied to any number of selected employees in one action, each getting their own new Salary Structure with everything copied over

## Module 3: Salary Component Management (Dynamic Earnings & Deductions) — ✅ Done

- [*] Dynamic earning components (e.g., Basic, House Rent, Bonus) — `SalaryComponent` (`type = earning`)
- [*] Dynamic deduction components (e.g., Tax, Loan, Insurance) — `SalaryComponent` (`type = deduction`)
- [*] Fixed-amount calculation type — `calculation_type = fixed`
- [*] Percentage-of-basic calculation type (auto-computed, e.g., House Rent = 40% of Basic) — `calculation_type = percentage`, computed fresh each payroll run in `PayrollService::resolveItemAmount()`
- [*] Assign components to an individual employee via their Salary Structure — `SalaryStructureItem`
- [*] Add/edit/remove components per company/admin — full CRUD (`SalaryComponentController`/`Service`)
- [*] Assign components to a *group* of employees at once — "Bulk Assign to Employees" action on a component adds/updates that one component on each selected employee's active structure without touching their other components (`SalaryStructureService::assignComponentToEmployees()`)
- [*] `is_taxable` flag on components

## Module 4: Custom Earnings & Deduction Heads (Food Allowance, Travel Allowance, Loan Deduction, etc.) — ✅ Done

- [*] Fully covered by Module 3's dynamic component system — any custom earning/deduction head (Food Allowance, Late Penalty, Loan Deduction, etc.) can be created as a `SalaryComponent` with no code changes
- [*] Per-day / per-occurrence custom penalties (e.g., "$10 per late day") — a third `calculation_type = per_occurrence` stores a rate on the component; the actual occurrence count is entered per employee when generating that period's Payroll (mirroring the existing commission-sales-amount pattern), and the resulting count is snapshotted on `payroll_items.occurrence_count` for audit

## Module 5: Overtime Rules (Hourly Rate, Fixed Rate, Tiered Overtime) — ✅ Done

- [*] Dynamic options on HRM Settings page for enable/disable overtime and other relevant setup — new "Overtime" card (`hrm_overtime_enabled`, calculation method, standard monthly hours)
- [*] Overtime calculation — three selectable methods, computed fresh each payroll run in `PayrollService::resolveOvertimeAmount()`:
  - Multiplier (e.g. 1.5x the employee's own derived hourly rate)
  - Flat Rate (a fixed $/hour, independent of the employee's salary)
  - Tiered (first N hours at one multiplier, next N hours at another, remainder at a third)
- [*] Overtime hours capture — `attendances.overtime_hours`, editable on the Attendance create/edit form, shown inline on the Attendance list
- [*] Overtime rule configuration (flat rate vs tiered rate) — covered by the calculation-method setting above; `payrolls.overtime_hours`/`overtime_amount` snapshot what was actually applied for transparency on each payslip

## Module 6: Attendance Integration (Late, Early Leave, Absent Rules) — ✅ Done

- [*] Attendance status tracked per day — `Attendance.attendance_status`, plus `hrm_late_grace_minutes` / `hrm_half_day_threshold_percent` settings (`HrmSettingsController`); a new `early_leave` status added to the enum, auto-detected on self-service check-out (`AttendancePortalController::checkoutStatus()`, formerly `halfDayStatus()`) alongside the pre-existing `half_day` detection
- [*] Unpaid-leave-based payroll deduction — `PayrollService::unpaidLeaveDeduction()` prorates salary loss for approved unpaid leave days/half-days over the configured pay period
- [*] Configurable pay-period window (calendar month or a cutoff-day cycle, e.g. 26th–25th) — `hrm_payroll_cutoff_day` setting, `PayrollService::payPeriod()`
- [*] Late-arrival deduction rules (e.g., "3 lates = $20 deducted") — new "Attendance Deductions" HRM Settings card: `hrm_late_deduction_enabled` + a free grace count + a flat $ per occurrence beyond grace, computed fresh each payroll run in `PayrollService::resolveAttendanceDeductions()`
- [*] Early-leave deduction rules — identical grace-count-then-per-occurrence-fee shape as late arrivals, keyed off the new `early_leave` attendance status (`hrm_early_leave_deduction_enabled`)
- [*] Absent-day automatic deduction (beyond approved unpaid leave) — an unapproved/unmarked `absent` day is docked at the period's per-day rate (`hrm_absent_deduction_enabled`); any absent date that already falls inside an approved *unpaid* Leave Request is excluded so the same day is never deducted twice (once via unpaid leave, once via the absent rule)
- [*] All three rules snapshot into a single `payrolls.attendance_deduction` column for payslip transparency, and are entirely optional via their own HRM Settings toggle
- Bug fix (found while verifying this module, applies to both `unpaidLeaveDeduction()` and the new attendance-deduction logic): `Carbon::diffInDays()` on a raw `endOfMonth()` timestamp returns a fractional float (e.g. `29.999999999988` instead of `29`) due to the trailing `23:59:59.999999`, silently mis-costing every per-day-rate deduction by a fraction of a percent. Fixed by normalizing both period boundaries to `startOfDay()` before diffing, in both methods.

## Module 7: Minimum Wage & Compliance Settings (Per Country/State Law) — ✅ Done

- [*] Minimum wage configuration (per country/state) — new `MinimumWageRule` master (Country, optional State, Wage Type [monthly/daily], Minimum Wage, Effective From). A state-specific rule overrides the country-wide one for that state only (same "more specific scope wins" rule Reorder Level already established); the currently-applicable rule for a given location/wage-type/date is the one with the latest `effective_date` not in the future — the same resolution logic Salary Structure already uses for its own "active" structure lookup, so a law change is handled by adding a new dated row rather than overwriting history. Country/state matching is case-insensitive. New "Minimum Wage Rules" screen under Payroll & Finance.
- [*] Retrofit: `employees.country` / `employees.state` (plain free-text, matching the existing city/country precedent already used elsewhere in this project) so an employee has a location to resolve a rule against — editable on the Employee create/edit form, shown on the Employee profile.
- [*] Validation blocking a salary/basic pay below the configured minimum wage — `MinimumWageComplianceService::violationMessage()` is the single shared check, wired into two places: `SalaryStructureRequest` (rejects a manually created/edited structure with a friendly form error naming the shortfall and location) and `SalaryTemplateService::assignToEmployees()` (a bulk-assigned employee below their location's minimum wage is skipped rather than aborting the whole batch, mirroring the existing `{updated, skipped}` shape from the Module 3 bulk-component-assign feature). Commission-based pay is deliberately exempt — it has no fixed rate to hold against a floor. An employee/location with no matching rule configured is never treated as a violation, only as "nothing to enforce."
- [*] Labor-law compliance rule engine — scoped to exactly what this checklist item calls for (a configurable minimum-wage floor with validation); no broader speculative rule engine was built beyond that.

---

## Post-Module Additions (requested after the 7 modules above, not part of the original doc)

- [x] **Download Payslip (PDF)** — a "Download Payslip" icon on each Payroll list row opens a standalone, print-to-PDF payslip in a new tab (`PayrollController::payslip()`), built on the same reusable `<x-print-document>` company-letterhead shell every other print view in this project uses. Shows Basic Salary, each earning/deduction `SalaryComponent` line, Overtime and Attendance Deduction lines when non-zero, a recomputed Unpaid Leave Deduction line (that figure has no dedicated column of its own on `payrolls`, unlike overtime/attendance — `PayrollService::unpaidLeaveDeductionFor()` re-derives the exact original figure from the stored record), a highlighted Net Salary box, an "amount in words" line (new `amount_in_words()` helper in `Helper.php`), and signature lines.
- [x] **Download Salary Certificate (PDF)** — a "Salary Certificate" item on each Employee's "View Records" dropdown opens a small Purpose/Issue Date form (plain GET, `target="_blank"`, the same "configure then print" flow Barcode Generator already established), which opens a formal "To Whomsoever It May Concern" certificate letter drawn from the employee's current active Salary Structure (`EmployeeController::salaryCertificate()`), with a component breakdown table for monthly pay and wording that adapts for daily-rate/commission structures. An employee with no active structure gets an honest "cannot be generated" message instead of a broken page.
- [x] **Payroll Compliance Report** — a new read-only screen (`admin.payroll-compliance-report.index`, under HRM's existing Reports group) that re-checks *every* active employee's current Salary Structure against *today's* Minimum Wage Rules on every page load — closing the exact gap flagged when Module 7 shipped: compliance was previously only checked at the moment a structure was created/assigned, with nothing to audit who might have since become non-compliant (a stricter rule added later, a corrected employee location, etc.). Filterable by Department / Pay Type / Compliance State, with stat tiles for Compliant / Non-Compliant / No Rule Configured / Exempt (Commission) / No Active Structure, and a per-row link into Salary Structures to fix a flagged employee.

## Summary

| Module | Status |
|---|---|
| 1. Multiple Salary Structure Templates | **Done** |
| 2. Company-wise Payroll Settings (Multi-tenant) | Deferred (explicitly deprioritized) |
| 3. Salary Component Management | **Done** |
| 4. Custom Earnings & Deduction Heads | **Done** (via Module 3's dynamic components) |
| 5. Overtime Rules | **Done** |
| 6. Attendance Integration | **Done** |
| 7. Minimum Wage & Compliance | **Done** |
