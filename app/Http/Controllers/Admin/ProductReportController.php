<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiscountRule;
use App\Models\Product;
use App\Models\ProductBundle;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class ProductReportController extends Controller
{
    /**
     * Display the Product Management reporting dashboard.
     */
    public function index(Request $request)
    {
        $totalProducts = Product::count();
        $activeProducts = Product::active()->count();

        $productsByCategory = Product::active()
            ->with('category')
            ->get()
            ->groupBy(fn($product) => $product->category->name ?? 'Uncategorized')
            ->map->count()
            ->sortDesc();

        $productsByBrand = Product::active()
            ->with('brand')
            ->get()
            ->groupBy(fn($product) => $product->brand->name ?? 'Unbranded')
            ->map->count()
            ->sortDesc();

        $productsWithoutPrice = Product::active()->whereNull('selling_price')->count();
        $avgSellingPrice = Product::active()->whereNotNull('selling_price')->avg('selling_price');

        $totalVariants = ProductVariant::count();
        $activeVariants = ProductVariant::active()->count();

        $totalBundles = ProductBundle::count();
        $activeDiscountRules = DiscountRule::active()->count();

        $productsMissingPrice = Product::active()
            ->whereNull('selling_price')
            ->orderBy('name')
            ->limit(10)
            ->get();

        return view('admin.product-reports.index', compact(
            'totalProducts',
            'activeProducts',
            'productsByCategory',
            'productsByBrand',
            'productsWithoutPrice',
            'avgSellingPrice',
            'totalVariants',
            'activeVariants',
            'totalBundles',
            'activeDiscountRules',
            'productsMissingPrice'
        ));
    }
}
