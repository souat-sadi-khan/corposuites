<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\ModuleMenu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class PurchaseMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $purchaseModule = Module::updateOrCreate(
            ['slug' => 'purchase'],
            [
                'name' => 'Purchase',
                'icon' => 'ri-truck-line',
                'description' => 'Manage vendors and the complete purchasing lifecycle',
                'status' => 1,
                'is_core' => false,
                'installed_at' => now(),
            ]
        );

        // Top-level: Vendors (direct link, no group)
        $this->menu($purchaseModule->id, 'purchase.vendors', null, 'Vendors', 'ri-building-4-line', 'admin.vendors.index', 0);

        // Top-level: Purchase Requests (direct link, no group) - primary transactional entity, same as Vendors
        $this->menu($purchaseModule->id, 'purchase.purchase-requests', null, 'Purchase Requests', 'ri-file-list-3-line', 'admin.purchase-requests.index', 1);

        // Top-level: RFQ Management (direct link, no group) - primary transactional entity, generated from approved Purchase Requests
        $this->menu($purchaseModule->id, 'purchase.rfqs', null, 'RFQ Management', 'ri-file-paper-2-line', 'admin.rfqs.index', 2);

        // Top-level: Supplier Quotations (direct link, no group) - vendor responses captured against an RFQ
        $this->menu($purchaseModule->id, 'purchase.supplier-quotations', null, 'Supplier Quotations', 'ri-file-list-2-line', 'admin.supplier-quotations.index', 3);

        // Top-level: Purchase Orders (direct link, no group) - primary transactional entity, placed with a vendor
        $this->menu($purchaseModule->id, 'purchase.purchase-orders', null, 'Purchase Orders', 'ri-shopping-cart-2-line', 'admin.purchase-orders.index', 4);

        // Top-level: Goods Receipts (direct link, no group) - physical intake of goods against a Purchase Order
        $this->menu($purchaseModule->id, 'purchase.goods-receipts', null, 'Goods Receipts', 'ri-inbox-archive-line', 'admin.goods-receipts.index', 5);

        // Top-level: Purchase Invoice Matching (direct link, no group) - vendor billed invoice matched against PO/Goods Receipt
        $this->menu($purchaseModule->id, 'purchase.purchase-invoices', null, 'Purchase Invoice Matching', 'ri-file-search-line', 'admin.purchase-invoices.index', 6);

        // Top-level: Debit Notes (direct link, no group) - reduces what is owed to a vendor
        $this->menu($purchaseModule->id, 'purchase.debit-notes', null, 'Debit Notes', 'ri-file-reduce-line', 'admin.debit-notes.index', 7);

        // Top-level: Purchase Returns (direct link, no group) - physical goods sent back to a vendor
        $this->menu($purchaseModule->id, 'purchase.purchase-returns', null, 'Purchase Returns', 'ri-arrow-go-back-line', 'admin.purchase-returns.index', 8);

        // Group: Purchase Masters
        $masters = $this->group($purchaseModule->id, 'purchase.group.masters', 'Purchase Masters', 'ri-database-2-line', 9);
        $this->menu($purchaseModule->id, 'purchase.vendor-groups', $masters->id, 'Vendor Groups', 'ri-group-line', 'admin.vendor-groups.index', 1);
        $this->menu($purchaseModule->id, 'purchase.vendor-performance-reviews', $masters->id, 'Vendor Performance', 'ri-star-line', 'admin.vendor-performance-reviews.index', 2);

        // Group: Reports (last, same placement precedent as HRM/CRM/Product Management/Sales)
        $reports = $this->group($purchaseModule->id, 'purchase.group.reports', 'Reports', 'ri-bar-chart-box-line', 10);
        $this->menu($purchaseModule->id, 'purchase.purchase-reports', $reports->id, 'Purchase Reports', 'ri-line-chart-line', 'admin.purchase-reports.index', 1);

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
