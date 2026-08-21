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

## Module 2: Company-wise Payroll Settings (Multi-tenant Based)

- [ ] Multi-tenant / multi-company data isolation
- [ ] Per-company payroll policy toggles (e.g., "Company A allows overtime, Company B doesn't")
- [ ] Company-specific settings scoping (current `SystemSetting`/`hrm_*` settings are global to the single installation, not per-company)

## Module 3: Salary Component Management (Dynamic Earnings & Deductions)

- [*] Dynamic earning components (e.g., Basic, House Rent, Bonus) — `SalaryComponent` (`type = earning`)
- [*] Dynamic deduction components (e.g., Tax, Loan, Insurance) — `SalaryComponent` (`type = deduction`)
- [*] Fixed-amount calculation type — `calculation_type = fixed`
- [*] Percentage-of-basic calculation type (auto-computed, e.g., House Rent = 40% of Basic) — `calculation_type = percentage`, computed in `PayrollService::buildTotals()`
- [*] Assign components to an individual employee via their Salary Structure — `SalaryStructureItem`
- [*] Add/edit/remove components per company/admin — full CRUD (`SalaryComponentController`/`Service`)
- [ ] Assign components to a *group* of employees at once (currently one structure per employee, no bulk/group assignment)
- [*] `is_taxable` flag on components

## Module 4: Custom Earnings & Deduction Heads (Food Allowance, Travel Allowance, Loan Deduction, etc.)

- [*] Fully covered by Module 3's dynamic component system — any custom earning/deduction head (Food Allowance, Late Penalty, Loan Deduction, etc.) can be created as a `SalaryComponent` with no code changes
- [ ] Per-day / per-occurrence custom penalties (e.g., "$10 per late day") — components are flat/percentage per pay period, not multiplied by an event count

## Module 5: Overtime Rules (Hourly Rate, Fixed Rate, Tiered Overtime)

- [ ] Overtime calculation of any kind (1.5× hourly rate, tiered rules, etc.)
- [ ] Overtime hours capture (no field on `Attendance` or elsewhere for overtime hours)
- [ ] Overtime rule configuration (flat rate vs tiered rate)

## Module 6: Attendance Integration (Late, Early Leave, Absent Rules)

- [*] Attendance status tracked per day — `Attendance.attendance_status`, plus `hrm_late_grace_minutes` / `hrm_half_day_threshold_percent` settings (`HrmSettingsController`)
- [*] Unpaid-leave-based payroll deduction — `PayrollService::unpaidLeaveDeduction()` prorates salary loss for approved unpaid leave days/half-days over the configured pay period
- [*] Configurable pay-period window (calendar month or a cutoff-day cycle, e.g. 26th–25th) — `hrm_payroll_cutoff_day` setting, `PayrollService::payPeriod()`
- [ ] Late-arrival deduction rules (e.g., "3 lates = $20 deducted") — not wired into payroll at all
- [ ] Absent-day automatic deduction (beyond approved unpaid leave) — an unapproved/unmarked absence is not deducted, only approved unpaid `LeaveRequest`s are
- [ ] Early-leave deduction rules

## Module 7: Minimum Wage & Compliance Settings (Per Country/State Law)

- [ ] Minimum wage configuration (per country/state)
- [ ] Validation blocking a salary/basic pay below the configured minimum wage
- [ ] Any labor-law compliance rule engine

---

## Summary

| Module | Status |
|---|---|
| 1. Multiple Salary Structure Templates | **Done** |
| 2. Company-wise Payroll Settings (Multi-tenant) | Not done |
| 3. Salary Component Management | Done |
| 4. Custom Earnings & Deduction Heads | Done (via Module 3's dynamic components) |
| 5. Overtime Rules | Not done |
| 6. Attendance Integration | Partially done (unpaid-leave deduction + pay-period config only; no late/absent/early-leave deduction) |
| 7. Minimum Wage & Compliance | Not done |
