<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    // 商品一覧（商品選択画面）
    public function index()
    {
        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('price')
            ->get();

        return view('products.index', compact('products'));
    }

    // 商品詳細（商品A/B/C詳細画面）
    public function show(Product $product)
    {
        abort_unless($product->is_active, 404);

        return view('products.show', compact('product'));
    }
}
