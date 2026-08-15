<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class DocumentationController extends Controller
{
    /**
     * Display the customer-facing documentation page.
     */
    public function index()
    {
        $sections = $this->sections();

        return view('admin.documentation.index', compact('sections'));
    }

    protected function sections(): array
    {
        return [
            [
                'id' => 'getting-started',
                'label' => 'Getting Started',
                'icon' => 'ri-compass-3-line',
                'title' => 'Getting Started',
                'body' => '
                    <p>Welcome! This guide explains how to use the Human Resource Management (HRM) part of the system. It is written in plain language so anyone on your team can follow it — no technical knowledge needed.</p>
                    <p>Everything you need is on the left-hand sidebar, under the <strong>HRM</strong> section. The menu is organised into groups so related things are easy to find:</p>
                    <ul>
                        <li><strong>Employees</strong> — your full staff list and their complete profile.</li>
                        <li><strong>Masters</strong> — the drop-down lists used across the system (job types, departments, shifts, holidays, and so on).</li>
                        <li><strong>Employee Records</strong> — documents, emergency contacts, bank details, education and work history.</li>
                        <li><strong>Career Events</strong> — transfers, promotions, resignations and terminations.</li>
                        <li><strong>Attendance & Leave</strong> — daily attendance and leave management.</li>
                        <li><strong>Payroll & Finance</strong> — salary structure, payroll runs, expense claims and staff loans.</li>
                        <li><strong>Performance</strong> — staff performance reviews.</li>
                        <li><strong>Reports</strong> — a dashboard summarising everything above.</li>
                    </ul>
                    <p>Most pages work the same way:</p>
                    <ul>
                        <li>Click <strong>Add</strong> (top right of any list) to create a new record.</li>
                        <li>Use the search box to quickly find something.</li>
                        <li>Click the pencil icon to edit a row, or the trash icon to delete it.</li>
                        <li>The switch in the Status column turns a record Active/Inactive without deleting it.</li>
                    </ul>
                ',
            ],
            [
                'id' => 'employees',
                'label' => 'Employees',
                'icon' => 'ri-user-line',
                'title' => 'Employees',
                'body' => '
                    <p>This is the main staff list. Every person you employ has one record here, and almost every other HR feature (documents, attendance, payroll, leave, etc.) is linked back to this record.</p>
                    <h6>Adding an employee</h6>
                    <p>Click <strong>Add Employee</strong>. Fill in their basic details — name, email, phone, date of joining, department, designation, employee type, employment status and shift. A photo is optional. Click <strong>Create</strong> to save.</p>
                    <h6>Viewing an employee\'s full profile</h6>
                    <p>Click the <strong>eye icon</strong> on any employee row. A window opens with tabs across the top — Profile, Documents, Emergency Contacts, Bank Accounts, Education, Experience, Career Events, Attendance, Leave, Salary & Payroll, Claims & Loans, and Performance. Click any tab to see that employee\'s history in one place, without leaving the page.</p>
                    <h6>Giving an employee a login to the system</h6>
                    <p>If an employee needs to log into this system themselves, click the <strong>key icon</strong> on their row (only shown if they don\'t already have a login). Enter an email, a password, and choose their role (this controls what they are allowed to see and do). Click <strong>Create Login</strong>.</p>
                    <h6>Related records menu</h6>
                    <p>Click the <strong>folder icon</strong> on any row to jump straight to that employee\'s documents, leave requests, payroll history, and everything else — already filtered to just that person.</p>
                    <h6>Importing and exporting employees</h6>
                    <p><strong>Export</strong> downloads a spreadsheet (CSV file) of every employee — useful for backups or sharing with other software.</p>
                    <p><strong>Import</strong> lets you add many employees at once. Click <strong>Import</strong>, then upload a CSV file with these column headings: Employee Code, First Name, Last Name, Email, Phone, Gender, Date of Birth, Date of Joining, Department, Designation. The Department and Designation names must match exactly what you already have set up under Masters. Rows with an email or employee code that already exists are skipped automatically, so it is safe to re-upload the same file.</p>
                ',
            ],
            [
                'id' => 'masters',
                'label' => 'Masters (Setup Lists)',
                'icon' => 'ri-database-2-line',
                'title' => 'Masters — Setting Up Your Drop-Down Lists',
                'body' => '
                    <p>Before adding employees, it helps to set up the lists you will choose from. These are simple, one-line records — a name, an optional description, and an Active/Inactive switch.</p>
                    <ul>
                        <li><strong>Employee Types</strong> — e.g. Full-Time, Part-Time, Contract, Intern.</li>
                        <li><strong>Employment Statuses</strong> — e.g. Probation, Confirmed, On Leave, Resigned.</li>
                        <li><strong>Departments</strong> — the teams in your company, e.g. Sales, Finance, IT.</li>
                        <li><strong>Designations</strong> — job titles, e.g. Manager, Executive. You can optionally attach a designation to a department (e.g. "Sales Manager" under the Sales department).</li>
                        <li><strong>Shifts</strong> — working hours, e.g. Morning Shift 9 AM–6 PM.</li>
                        <li><strong>Holidays</strong> — company holidays, with a calendar view button so you can see them laid out on a monthly calendar.</li>
                        <li><strong>Leave Types</strong> — e.g. Annual Leave, Sick Leave, Unpaid Leave — including how many days are allowed and whether it is paid.</li>
                        <li><strong>Salary Components</strong> — the building blocks of a pay packet, e.g. Basic Pay, House Rent Allowance, Tax Deduction. Each one is marked as an Earning or a Deduction, and as either a Fixed amount or a Percentage.</li>
                        <li><strong>Skills</strong> — a simple list of skills you may want to track.</li>
                    </ul>
                    <p>Set these up once, and they will appear automatically in the drop-down menus everywhere else in the system (like the Employee form).</p>
                ',
            ],
            [
                'id' => 'employee-records',
                'label' => 'Employee Records',
                'icon' => 'ri-folder-user-line',
                'title' => 'Employee Records',
                'body' => '
                    <p>These pages hold extra information about each employee. Every record here is tied to one employee, so you always pick which employee it belongs to first.</p>
                    <h6>Documents</h6>
                    <p>Upload important files for an employee — ID copies, contracts, certificates. Each document can have an expiry date, which is useful for things like visas or licenses that need renewing.</p>
                    <h6>Emergency Contacts</h6>
                    <p>Store who to contact in an emergency — name, relationship, and phone number. You can mark one contact as the "Primary" contact.</p>
                    <h6>Bank Accounts</h6>
                    <p>Store an employee\'s bank details for salary payments — bank name, account holder, account number, branch, and IFSC/SWIFT code.</p>
                    <h6>Education</h6>
                    <p>Record qualifications — degree, institution, field of study, start/end year, and grade.</p>
                    <h6>Experience</h6>
                    <p>Record previous jobs — company, job title, start and end dates (or mark it as their current job).</p>
                    <p>Tip: from any employee\'s profile view (the eye icon on the Employees page), you can see all of the above in one place without switching pages.</p>
                ',
            ],
            [
                'id' => 'career-events',
                'label' => 'Career Events',
                'icon' => 'ri-route-line',
                'title' => 'Career Events',
                'body' => '
                    <p>These pages record the big moments in an employee\'s time with your company.</p>
                    <h6>Transfers</h6>
                    <p>Record a move from one department or designation to another. When you save a transfer, the employee\'s current department/designation is updated automatically to the new one — you don\'t need to also go and edit their profile.</p>
                    <h6>Promotions</h6>
                    <p>Record a change in job title and/or salary. Like transfers, saving a promotion automatically updates the employee\'s designation.</p>
                    <h6>Resignations</h6>
                    <p>Record when an employee has resigned — their resignation date, last working day, and notice period.</p>
                    <h6>Terminations</h6>
                    <p>Record when an employee\'s employment has ended, and whether it was voluntary or involuntary, along with the reason.</p>
                    <p>All of these are simply historical records — they help you keep a clear timeline of what happened and when, for every employee.</p>
                ',
            ],
            [
                'id' => 'attendance-leave',
                'label' => 'Attendance & Leave',
                'icon' => 'ri-calendar-check-line',
                'title' => 'Attendance & Leave',
                'body' => '
                    <h6>Attendance</h6>
                    <p>Record each employee\'s attendance for a specific date — Present, Absent, Half Day, On Leave, or Late — along with their check-in and check-out time.</p>
                    <h6>Attendance Adjustments</h6>
                    <p>If someone\'s check-in/check-out time was recorded incorrectly, or they forgot to check in, submit an adjustment request here with the correct time and a reason. It will show as <strong>Pending</strong> until someone with the right access clicks <strong>Approve</strong> — once approved, the correction is automatically applied to that day\'s Attendance record. You can also <strong>Reject</strong> a request if it is not valid.</p>
                    <h6>Leave Balances</h6>
                    <p>Shows how many leave days each employee has been given for each leave type in a given year, how many they have used, and how many remain.</p>
                    <h6>Leave Requests</h6>
                    <p>When an employee wants to take leave, a request is created here with a start date, end date, and a reason. The number of days is worked out automatically. Requests start as <strong>Pending</strong>. Clicking <strong>Approve</strong> automatically deducts those days from the employee\'s Leave Balance; clicking <strong>Reject</strong> does not.</p>
                ',
            ],
            [
                'id' => 'payroll-finance',
                'label' => 'Payroll & Finance',
                'icon' => 'ri-hand-coin-line',
                'title' => 'Payroll & Finance',
                'body' => '
                    <h6>Salary Structures</h6>
                    <p>This is where you define how much an employee is paid. Set their Basic Salary, then add Salary Components (from the Masters list) such as allowances or deductions, each with its own amount. The total (Gross Salary) is calculated for you automatically as you add components.</p>
                    <h6>Payroll</h6>
                    <p>Click <strong>Generate Payroll</strong>, choose the employee, month and year — the system automatically pulls their current Salary Structure and works out their pay for that month. Once you have paid them, click the <strong>Mark as Paid</strong> button on that row.</p>
                    <h6>Expense Claims</h6>
                    <p>Employees can submit work-related expenses to be reimbursed — category, amount, date, description, and an optional receipt file. Claims start as <strong>Pending</strong>; use <strong>Approve</strong> or <strong>Reject</strong> to process them.</p>
                    <h6>Employee Loans</h6>
                    <p>Record a loan given to an employee — the total amount and how many installments it will be repaid over (the amount per installment is calculated automatically). Once approved, use the <strong>Record Payment</strong> button each time an installment is paid, and the remaining balance updates automatically.</p>
                ',
            ],
            [
                'id' => 'performance',
                'label' => 'Performance',
                'icon' => 'ri-line-chart-line',
                'title' => 'Performance Reviews',
                'body' => '
                    <p>Record a formal review for an employee — the review period, who conducted it, a rating out of 5, their strengths, areas to improve, and goals going forward. Use this to keep a written history of how each employee is performing over time.</p>
                ',
            ],
            [
                'id' => 'reports',
                'label' => 'Reports',
                'icon' => 'ri-bar-chart-box-line',
                'title' => 'HR Reports',
                'body' => '
                    <p>This is a one-page overview of your whole HR setup — total and active employees, staff grouped by department, today\'s attendance at a glance, how many leave requests and expense claims are waiting for approval, outstanding loan balances, and this month\'s payroll totals. It is read-only — use it to quickly see how things stand without digging through each module individually.</p>
                ',
            ],
        ];
    }
}
