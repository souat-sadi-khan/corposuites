<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\ModuleMenu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class AccountingMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accountingModule = Module::updateOrCreate(
            ['slug' => 'accounting'],
            [
                'name' => 'Accounting',
                'icon' => 'ri-calculator-line',
                'description' => 'Manage complete financial operations and reporting',
                'status' => 1,
                'is_core' => false,
                'installed_at' => now(),
            ]
        );

        // Top-level: Chart of Accounts (direct link, no group) - first, foundational Accounting entity
        $this->menu($accountingModule->id, 'accounting.chart-of-accounts', null, 'Chart of Accounts', 'ri-file-list-3-line', 'admin.chart-of-accounts.index', 0);

        // Top-level: Journal Entries (direct link, no group) - primary transactional entity, records double-entry postings
        $this->menu($accountingModule->id, 'accounting.journal-entries', null, 'Journal Entries', 'ri-book-2-line', 'admin.journal-entries.index', 1);

        // Top-level: General Ledger (direct link, no group) - read-only per-account running balance report built from posted Journal Entries
        $this->menu($accountingModule->id, 'accounting.general-ledger', null, 'General Ledger', 'ri-file-chart-2-line', 'admin.general-ledger.index', 2);

        // Top-level: Cash Book (direct link, no group) - read-only receipts/payments running balance scoped to Cash/Bank accounts
        $this->menu($accountingModule->id, 'accounting.cash-book', null, 'Cash Book', 'ri-safe-2-line', 'admin.cash-book.index', 3);

        // Top-level: Bank Transactions (direct link, no group) - primary transactional entity, records deposits/withdrawals against a bank account
        $this->menu($accountingModule->id, 'accounting.bank-transactions', null, 'Bank Transactions', 'ri-exchange-dollar-line', 'admin.bank-transactions.index', 4);

        // Top-level: Bank Reconciliation (direct link, no group) - primary transactional entity, matches statement transactions against recorded ones
        $this->menu($accountingModule->id, 'accounting.bank-reconciliations', null, 'Bank Reconciliation', 'ri-file-check-line', 'admin.bank-reconciliations.index', 5);

        // Top-level: Accounts Receivable (direct link, no group) - read-only per-customer outstanding balance report built from Sales Invoices
        $this->menu($accountingModule->id, 'accounting.accounts-receivable', null, 'Accounts Receivable', 'ri-user-received-2-line', 'admin.accounts-receivable.index', 6);

        // Top-level: Accounts Payable (direct link, no group) - read-only per-vendor outstanding balance report built from Purchase Invoices
        $this->menu($accountingModule->id, 'accounting.accounts-payable', null, 'Accounts Payable', 'ri-user-shared-2-line', 'admin.accounts-payable.index', 7);

        // Top-level: Payment Receive (direct link, no group) - primary transactional entity, records a customer payment and allocates it against open Sales Invoices
        $this->menu($accountingModule->id, 'accounting.payment-receives', null, 'Payment Receive', 'ri-hand-coin-line', 'admin.payment-receives.index', 8);

        // Top-level: Payment Make (direct link, no group) - primary transactional entity, records a vendor payment and allocates it against open Purchase Invoices
        $this->menu($accountingModule->id, 'accounting.payment-makes', null, 'Payment Make', 'ri-hand-coin-line', 'admin.payment-makes.index', 9);

        // Top-level: Trial Balance (direct link, no group) - read-only all-accounts debit/credit snapshot built from posted Journal Entries
        $this->menu($accountingModule->id, 'accounting.trial-balance', null, 'Trial Balance', 'ri-scales-3-line', 'admin.trial-balance.index', 10);

        // Top-level: Profit and Loss (direct link, no group) - read-only revenue-less-expenses statement for a period
        $this->menu($accountingModule->id, 'accounting.profit-and-loss', null, 'Profit and Loss', 'ri-line-chart-line', 'admin.profit-and-loss.index', 11);

        // Top-level: Balance Sheet (direct link, no group) - read-only assets vs liabilities+equity position as of a date
        $this->menu($accountingModule->id, 'accounting.balance-sheet', null, 'Balance Sheet', 'ri-scales-line', 'admin.balance-sheet.index', 12);

        // Top-level: Cash Flow (direct link, no group) - read-only direct-method cash movement statement by activity
        $this->menu($accountingModule->id, 'accounting.cash-flow', null, 'Cash Flow', 'ri-exchange-dollar-line', 'admin.cash-flow.index', 13);

        // Group: Accounting Masters
        $masters = $this->group($accountingModule->id, 'accounting.group.masters', 'Accounting Masters', 'ri-database-2-line', 14);
        $this->menu($accountingModule->id, 'accounting.account-types', $masters->id, 'Account Types', 'ri-price-tag-3-line', 'admin.account-types.index', 1);
        $this->menu($accountingModule->id, 'accounting.finance-bank-accounts', $masters->id, 'Bank Accounts', 'ri-bank-line', 'admin.finance-bank-accounts.index', 2);
        $this->menu($accountingModule->id, 'accounting.tax-rates', $masters->id, 'Tax Management', 'ri-percent-line', 'admin.tax-rates.index', 3);

        // Group: Reports (last among Accounting's menu groups, same placement
        // precedent as HRM's/CRM's/Product Management's/Sales'/Purchase's/
        // Inventory's own "Reports" groups)
        $reports = $this->group($accountingModule->id, 'accounting.group.reports', 'Reports', 'ri-bar-chart-box-line', 15);
        $this->menu($accountingModule->id, 'accounting.financial-reports', $reports->id, 'Financial Reports', 'ri-funds-line', 'admin.financial-reports.index', 1);

        Cache::forget('admin.module.menus');
    }

    protected function group(int $moduleId, string $name, string $label, string $icon, int $order): ModuleMenu
    {
        return ModuleMenu::updateOrCreate(
            ['module_id' => $moduleId, 'name' => $name],
            [
                'parent_id' => null,
                'label' => $label,
                'icon' => $icon,
                'route' => null,
                'permission' => null,
                'order' => $order,
                'status' => 1,
            ]
        );
    }

    protected function menu(int $moduleId, string $name, ?int $parentId, string $label, string $icon, string $route, int $order): ModuleMenu
    {
        return ModuleMenu::updateOrCreate(
            ['module_id' => $moduleId, 'name' => $name],
            [
                'parent_id' => $parentId,
                'label' => $label,
                'icon' => $icon,
                'route' => $route,
                'permission' => null,
                'order' => $order,
                'status' => 1,
            ]
        );
    }
}
