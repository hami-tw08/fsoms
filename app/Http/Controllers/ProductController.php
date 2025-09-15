<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    // フロント：一覧
    public function index()
    {
        $products = Product::query()
            ->where('is_active', true)
            ->orderByDesc('id')
            ->paginate(9);

        return view('products.index', compact('products'));
    }

    // フロント：詳細（slugバインド）
    public function show(Product $product)
    {
        abort_unless($product->is_active, 404);
        return view('products.show', compact('product'));
    }

    // --- ここから管理（店舗）側 ---
    public function create()
    {
        $product = new Product();
        return view('admin.products.create', compact('product'));
    }

    public function store(ProductRequest $request)
    {
        $data = $request->validated();

        // 画像アップロード（任意）
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', 'public');
            // image_urlはAccessorで自動解決するので無理に入れなくてOK（外部URL入力があればそちら優先）
        }

        $product = Product::create($data);

        return redirect()->route('admin.products.edit', $product)->with('status', '商品を作成しました');
    }

    public function edit(Product $product)
    {
        return view('admin.products.edit', compact('product'));
    }

    public function update(ProductRequest $request, Product $product)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            // 旧ファイル削除（任意）
            if ($product->image_path && Storage::disk('public')->exists($product->image_path)) {
                Storage::disk('public')->delete($product->image_path);
            }
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        $product->update($data);

        return redirect()->route('admin.products.edit', $product)->with('status', '商品を更新しました');
    }

    public function destroy(Product $product)
    {
        // 画像削除（任意）
        if ($product->image_path && Storage::disk('public')->exists($product->image_path)) {
            Storage::disk('public')->delete($product->image_path);
        }
        $product->delete();

        return redirect()->route('products.index')->with('status', '商品を削除しました');
    }
}
