<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\ModuleMenu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class SalesMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $salesModule = Module::updateOrCreate(
            ['slug' => 'sales'],
            [
                'name' => 'Sales',
                'icon' => 'ri-shopping-cart-2-line',
                'description' => 'Convert opportunities into revenue and manage the complete sales lifecycle',
                'status' => 1,
                'is_core' => false,
                'installed_at' => now(),
            ]
        );

        // Top-level: Customers (direct link, no group)
        $this->menu($salesModule->id, 'sales.customers', null, 'Customers', 'ri-user-3-line', 'admin.customers.index', 0);

        // Top-level: Quotations (direct link, no group) - primary transactional entity, same as Customers
        $this->menu($salesModule->id, 'sales.quotations', null, 'Quotations', 'ri-file-list-3-line', 'admin.sales-quotations.index', 1);

        // Top-level: Orders (direct link, no group) - primary transactional entity, same as Customers/Quotations
        $this->menu($salesModule->id, 'sales.orders', null, 'Orders', 'ri-shopping-bag-3-line', 'admin.sales-orders.index', 2);

        // Top-level: Deliveries (direct link, no group) - primary transactional entity, same as Customers/Quotations/Orders
        $this->menu($salesModule->id, 'sales.deliveries', null, 'Deliveries', 'ri-truck-line', 'admin.deliveries.index', 3);

        // Top-level: Delivery Notes (direct link, no group) - primary transactional entity, same as Customers/Quotations/Orders/Deliveries
        $this->menu($salesModule->id, 'sales.delivery-notes', null, 'Delivery Notes', 'ri-file-list-line', 'admin.delivery-notes.index', 4);

        // Top-level: Invoices (direct link, no group) - primary transactional entity, same as Customers/Quotations/Orders/Deliveries/Delivery Notes
        $this->menu($salesModule->id, 'sales.invoices', null, 'Invoices', 'ri-bill-line', 'admin.sales-invoices.index', 5);

        // Top-level: Credit Notes (direct link, no group) - primary transactional entity, same as Customers/Quotations/Orders/Deliveries/Delivery Notes/Invoices
        $this->menu($salesModule->id, 'sales.credit-notes', null, 'Credit Notes', 'ri-refund-2-line', 'admin.credit-notes.index', 6);

        // Top-level: Returns (direct link, no group) - primary transactional entity, same as Customers/Quotations/Orders/Deliveries/Delivery Notes/Invoices/Credit Notes
        $this->menu($salesModule->id, 'sales.returns', null, 'Returns', 'ri-arrow-go-back-line', 'admin.sales-returns.index', 7);

        // Top-level: POS Terminal (direct link, no group) - the live checkout screen
        $this->menu($salesModule->id, 'sales.pos-terminal', null, 'POS Terminal', 'ri-store-2-line', 'admin.pos.terminal', 8);

        // Top-level: POS Sales (direct link, no group) - sales history for the terminal
        $this->menu($salesModule->id, 'sales.pos-sales', null, 'POS Sales', 'ri-receipt-line', 'admin.pos.index', 9);

        // Group: Sales Masters
        $masters = $this->group($salesModule->id, 'sales.group.masters', 'Sales Masters', 'ri-database-2-line', 10);
        $this->menu($salesModule->id, 'sales.customer-groups', $masters->id, 'Customer Groups', 'ri-group-line', 'admin.customer-groups.index', 1);
        $this->menu($salesModule->id, 'sales.payment-terms', $masters->id, 'Payment Terms', 'ri-calendar-2-line', 'admin.payment-terms.index', 2);
        $this->menu($salesModule->id, 'sales.price-lists', $masters->id, 'Price Lists', 'ri-price-tag-2-line', 'admin.price-lists.index', 3);
        $this->menu($salesModule->id, 'sales.sales-targets', $masters->id, 'Sales Targets', 'ri-trophy-line', 'admin.sales-targets.index', 4);
        $this->menu($salesModule->id, 'sales.sales-commissions', $masters->id, 'Sales Commissions', 'ri-money-dollar-circle-line', 'admin.sales-commissions.index', 5);

        // Group: Reports (last, same placement precedent as HRM/CRM/Product Management's own "Reports" groups)
        $reports = $this->group($salesModule->id, 'sales.group.reports', 'Reports', 'ri-bar-chart-2-line', 11);
        $this->menu($salesModule->id, 'sales.sales-reports', $reports->id, 'Sales Reports', 'ri-line-chart-line', 'admin.sales-reports.index', 1);

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
