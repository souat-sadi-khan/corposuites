<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\ModuleMenu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class AssetMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $assetModule = Module::updateOrCreate(
            ['slug' => 'asset-management'],
            [
                'name' => 'Asset Management',
                'icon' => 'ri-hard-drive-2-line',
                'description' => 'Track company assets, assignment, maintenance and depreciation',
                'status' => 1,
                'is_core' => false,
                'installed_at' => now(),
            ]
        );

        // Top-level: Asset Register (direct link, no group) - the module's primary entity
        $this->menu($assetModule->id, 'asset.assets', null, 'Asset Register', 'ri-hard-drive-3-line', 'admin.assets.index', 0);

        // Top-level: Asset Purchase Information (direct link, no group)
        $this->menu($assetModule->id, 'asset.asset-purchases', null, 'Purchase Information', 'ri-money-dollar-box-line', 'admin.asset-purchases.index', 1);

        // Top-level: Asset Assignment (direct link, no group)
        $this->menu($assetModule->id, 'asset.asset-assignments', null, 'Asset Assignment', 'ri-user-shared-2-line', 'admin.asset-assignments.index', 2);

        // Top-level: Employee Asset Tracking (direct link, no group) - read-only per-employee holdings view
        $this->menu($assetModule->id, 'asset.employee-asset-tracking', null, 'Employee Asset Tracking', 'ri-team-line', 'admin.employee-asset-tracking.index', 3);

        // Top-level: Asset Location Tracking (the movement screen is the one
        // used day-to-day; the locations master sits under Asset Masters)
        $this->menu($assetModule->id, 'asset.asset-location-movements', null, 'Location Tracking', 'ri-route-line', 'admin.asset-location-movements.index', 4);

        // Top-level: Maintenance Schedule (direct link, no group)
        $this->menu($assetModule->id, 'asset.maintenance-schedules', null, 'Maintenance Schedule', 'ri-tools-line', 'admin.asset-maintenance-schedules.index', 5);

        // Top-level: Maintenance History (direct link, no group)
        $this->menu($assetModule->id, 'asset.maintenance-records', null, 'Maintenance History', 'ri-file-history-line', 'admin.asset-maintenance-records.index', 6);

        // Top-level: Depreciation Calculation (read-only, derived figures)
        $this->menu($assetModule->id, 'asset.depreciation', null, 'Depreciation', 'ri-line-chart-line', 'admin.asset-depreciation.index', 7);

        // Top-level: Disposal Management (direct link, no group)
        $this->menu($assetModule->id, 'asset.disposals', null, 'Disposal Management', 'ri-delete-bin-6-line', 'admin.asset-disposals.index', 8);

        // Group: Asset Masters
        $masters = $this->group($assetModule->id, 'asset.group.masters', 'Asset Masters', 'ri-database-2-line', 9);
        $this->menu($assetModule->id, 'asset.asset-categories', $masters->id, 'Asset Categories', 'ri-price-tag-3-line', 'admin.asset-categories.index', 1);
        $this->menu($assetModule->id, 'asset.asset-locations', $masters->id, 'Asset Locations', 'ri-map-pin-line', 'admin.asset-locations.index', 2);

        // Group: Reports (last among the module's menu groups, the same
        // placement every other module's Reports group has)
        $reports = $this->group($assetModule->id, 'asset.group.reports', 'Reports', 'ri-bar-chart-box-line', 10);
        $this->menu($assetModule->id, 'asset.asset-reports', $reports->id, 'Asset Reports', 'ri-funds-line', 'admin.asset-reports.index', 1);

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
