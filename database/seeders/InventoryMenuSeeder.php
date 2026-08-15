<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\ModuleMenu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class InventoryMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $inventoryModule = Module::updateOrCreate(
            ['slug' => 'inventory'],
            [
                'name' => 'Inventory',
                'icon' => 'ri-archive-2-line',
                'description' => 'Manage warehouses and stock across locations',
                'status' => 1,
                'is_core' => false,
                'installed_at' => now(),
            ]
        );

        // Top-level: Warehouses (direct link, no group)
        $this->menu($inventoryModule->id, 'inventory.warehouses', null, 'Warehouses', 'ri-building-2-line', 'admin.warehouses.index', 0);

        // Top-level: Stock Entry (direct link, no group) - primary transactional entity, records stock received into a warehouse
        $this->menu($inventoryModule->id, 'inventory.stock-entries', null, 'Stock Entry', 'ri-inbox-archive-line', 'admin.stock-entries.index', 1);

        // Top-level: Opening Stock (direct link, no group) - primary transactional entity, records initial stock balances
        $this->menu($inventoryModule->id, 'inventory.opening-stocks', null, 'Opening Stock', 'ri-file-add-line', 'admin.opening-stocks.index', 2);

        // Top-level: Stock Adjustment (direct link, no group) - primary transactional entity, corrects stock for damage/loss/miscounts
        $this->menu($inventoryModule->id, 'inventory.stock-adjustments', null, 'Stock Adjustment', 'ri-equalizer-line', 'admin.stock-adjustments.index', 3);

        // Top-level: Stock Transfer (direct link, no group) - primary transactional entity, moves stock between warehouses
        $this->menu($inventoryModule->id, 'inventory.stock-transfers', null, 'Stock Transfer', 'ri-exchange-line', 'admin.stock-transfers.index', 4);

        // Top-level: Stock Count (direct link, no group) - primary transactional entity, records physical stock counts
        $this->menu($inventoryModule->id, 'inventory.stock-counts', null, 'Stock Count', 'ri-list-check-3', 'admin.stock-counts.index', 5);

        // Top-level: Inventory Transactions (direct link, no group) - unified read-only ledger + live stock balance across all movement modules
        $this->menu($inventoryModule->id, 'inventory.inventory-transactions', null, 'Inventory Transactions', 'ri-file-list-3-line', 'admin.inventory-transactions.index', 6);

        // Top-level: Stock Valuation (direct link, no group) - read-only report valuing current stock at weighted-average cost
        $this->menu($inventoryModule->id, 'inventory.stock-valuation', null, 'Stock Valuation', 'ri-money-dollar-circle-line', 'admin.stock-valuation.index', 7);

        // Top-level: Low Stock Alerts (direct link, no group) - read-only report comparing current balance against Reorder Level
        $this->menu($inventoryModule->id, 'inventory.low-stock-alerts', null, 'Low Stock Alerts', 'ri-alarm-warning-fill', 'admin.low-stock-alerts.index', 8);

        // Group: Inventory Masters
        $masters = $this->group($inventoryModule->id, 'inventory.group.masters', 'Inventory Masters', 'ri-database-2-line', 9);
        $this->menu($inventoryModule->id, 'inventory.warehouse-locations', $masters->id, 'Warehouse Locations', 'ri-map-pin-line', 'admin.warehouse-locations.index', 1);
        $this->menu($inventoryModule->id, 'inventory.product-batches', $masters->id, 'Batch Management', 'ri-barcode-line', 'admin.product-batches.index', 2);
        $this->menu($inventoryModule->id, 'inventory.product-serials', $masters->id, 'Serial Number Management', 'ri-qr-code-line', 'admin.product-serials.index', 3);
        $this->menu($inventoryModule->id, 'inventory.reorder-levels', $masters->id, 'Reorder Level', 'ri-alarm-warning-line', 'admin.reorder-levels.index', 4);

        // Group: Reports
        $reports = $this->group($inventoryModule->id, 'inventory.group.reports', 'Reports', 'ri-bar-chart-2-line', 10);
        $this->menu($inventoryModule->id, 'inventory.inventory-reports', $reports->id, 'Inventory Reports', 'ri-file-chart-2-line', 'admin.inventory-reports.index', 1);

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
