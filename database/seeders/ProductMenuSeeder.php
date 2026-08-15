<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\ModuleMenu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class ProductMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $productModule = Module::updateOrCreate(
            ['slug' => 'product-management'],
            [
                'name' => 'Product Management',
                'icon' => 'ri-box-3-line',
                'description' => 'Product master data connected with Sales, Purchase, and Inventory',
                'status' => 1,
                'is_core' => false,
                'installed_at' => now(),
            ]
        );

        // Top-level: Products (direct link, no group)
        $this->menu($productModule->id, 'product.products', null, 'Products', 'ri-box-3-line', 'admin.products.index', 0);

        // Top-level: Product Variants (direct link, no group)
        $this->menu($productModule->id, 'product.product-variants', null, 'Product Variants', 'ri-stack-line', 'admin.product-variants.index', 1);

        // Top-level: Product Bundles (direct link, no group)
        $this->menu($productModule->id, 'product.product-bundles', null, 'Product Bundles', 'ri-archive-2-line', 'admin.product-bundles.index', 2);

        // Group: Product Masters
        $masters = $this->group($productModule->id, 'product.group.masters', 'Product Masters', 'ri-database-2-line', 3);
        $this->menu($productModule->id, 'product.categories', $masters->id, 'Categories', 'ri-price-tag-3-line', 'admin.categories.index', 1);
        $this->menu($productModule->id, 'product.brands', $masters->id, 'Brands', 'ri-copyright-line', 'admin.brands.index', 2);
        $this->menu($productModule->id, 'product.units', $masters->id, 'Units', 'ri-ruler-2-line', 'admin.units.index', 3);
        $this->menu($productModule->id, 'product.unit-conversions', $masters->id, 'Unit Conversion', 'ri-arrow-left-right-line', 'admin.unit-conversions.index', 4);
        $this->menu($productModule->id, 'product.product-attributes', $masters->id, 'Product Attributes', 'ri-list-settings-line', 'admin.product-attributes.index', 5);
        $this->menu($productModule->id, 'product.attribute-values', $masters->id, 'Attribute Values', 'ri-list-check-2', 'admin.attribute-values.index', 6);
        $this->menu($productModule->id, 'product.price-tiers', $masters->id, 'Price Tiers', 'ri-price-tag-2-line', 'admin.price-tiers.index', 7);
        $this->menu($productModule->id, 'product.product-prices', $masters->id, 'Product Prices', 'ri-money-dollar-circle-line', 'admin.product-prices.index', 8);
        $this->menu($productModule->id, 'product.discount-rules', $masters->id, 'Discount Rules', 'ri-percent-line', 'admin.discount-rules.index', 9);
        $this->menu($productModule->id, 'product.barcode-generator', $masters->id, 'Barcode Generator', 'ri-barcode-line', 'admin.barcode-generator.index', 10);

        // Group: Reports
        $reports = $this->group($productModule->id, 'product.group.reports', 'Reports', 'ri-bar-chart-box-line', 4);
        $this->menu($productModule->id, 'product.product-reports', $reports->id, 'Product Reports', 'ri-bar-chart-box-line', 'admin.product-reports.index', 1);

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
