<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class BarcodeGeneratorController extends Controller
{
    /**
     * Display the product selection screen.
     */
    public function index(Request $request)
    {
        $products = Product::active()->orderBy('name')->get();

        return view('admin.barcode-generator.index', compact('products'));
    }

    /**
     * Render the printable barcode label sheet for the selected products.
     */
    public function print(Request $request)
    {
        $request->validate([
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'exists:products,id',
            'quantity' => 'nullable|array',
            'quantity.*' => 'nullable|integer|min:1|max:100',
        ]);

        $products = Product::whereIn('id', $request->product_ids)->get();
        $quantities = $request->input('quantity', []);

        $labels = [];
        foreach ($products as $product) {
            $qty = (int) ($quantities[$product->id] ?? 1);
            for ($i = 0; $i < $qty; $i++) {
                $labels[] = $product;
            }
        }

        return view('admin.barcode-generator.print', compact('labels'));
    }
}
