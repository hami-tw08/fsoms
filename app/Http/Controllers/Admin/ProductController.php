<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::orderByDesc('created_at')->paginate(20);
        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
        $product = new Product();
        return view('admin.products.create', compact('product'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        // 画像アップロード（任意）
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        // 外部URLが入ってたら image_path は残してOK（表示優先は image_url）
        $product = Product::create($data);

        return redirect()->route('admin.products.edit', $product)
            ->with('success', '商品を作成しました。');
    }

    public function edit(Product $product)
    {
        return view('admin.products.edit', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validateData($request, $product->id);

        if ($request->hasFile('image')) {
            // 旧ファイル削除（任意）
            if ($product->image_path && Storage::disk('public')->exists($product->image_path)) {
                Storage::disk('public')->delete($product->image_path);
            }
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        $product->update($data);

        return back()->with('success', '商品を更新しました。');
    }

    public function destroy(Product $product)
    {
        if ($product->image_path && Storage::disk('public')->exists($product->image_path)) {
            Storage::disk('public')->delete($product->image_path);
        }
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', '商品を削除しました。');
    }

    private function validateData(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'name'        => ['required','string','max:255'],
            'slug'        => ['nullable','string','max:255','unique:products,slug'.($id?','.$id:'')],
            'price'       => ['required','integer','min:0'],
            'description' => ['nullable','string'],
            'image'       => ['nullable','image','max:5120'], // 5MB
            'image_url'   => ['nullable','url','max:2048'],
            'is_active'   => ['nullable','boolean'],
        ], [], [
            'name' => '商品名','slug' => 'スラッグ','price' => '価格','description' => '説明',
            'image' => '画像','image_url' => '外部画像URL','is_active' => '販売中'
        ]) + [
            // checkbox未チェック時に false を入れる
            'is_active' => (bool) $request->boolean('is_active', false),
        ];
    }
}
