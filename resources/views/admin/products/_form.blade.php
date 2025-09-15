@csrf
<div class="grid md:grid-cols-2 gap-6">
  <div class="space-y-3">
    <div>
      <label class="label"><span class="label-text">商品名 *</span></label>
      <input type="text" name="name" value="{{ old('name', $product->name) }}" class="input input-bordered w-full" required>
      @error('name')<p class="text-error text-sm">{{ $message }}</p>@enderror
    </div>

    <div>
      <label class="label"><span class="label-text">スラッグ（空なら自動生成）</span></label>
      <input type="text" name="slug" value="{{ old('slug', $product->slug) }}" class="input input-bordered w-full">
      @error('slug')<p class="text-error text-sm">{{ $message }}</p>@enderror
    </div>

    <div>
      <label class="label"><span class="label-text">価格(円) *</span></label>
      <input type="number" name="price" min="0" value="{{ old('price', $product->price) }}" class="input input-bordered w-full" required>
      @error('price')<p class="text-error text-sm">{{ $message }}</p>@enderror
    </div>

    <div>
      <label class="label"><span class="label-text">説明</span></label>
      <textarea name="description" rows="6" class="textarea textarea-bordered w-full">{{ old('description', $product->description) }}</textarea>
      @error('description')<p class="text-error text-sm">{{ $message }}</p>@enderror
    </div>
  </div>

  <div class="space-y-3">
    <div>
      <label class="label"><span class="label-text">画像アップロード（任意）</span></label>
      <input type="file" name="image" class="file-input file-input-bordered w-full" accept="image/*">
      @error('image')<p class="text-error text-sm">{{ $message }}</p>@enderror
    </div>

    <div>
      <label class="label"><span class="label-text">外部画像URL（任意／アップロードより優先）</span></label>
      <input type="url" name="image_url" value="{{ old('image_url', $product->getRawOriginal('image_url')) }}" class="input input-bordered w-full" placeholder="https://...">
      @error('image_url')<p class="text-error text-sm">{{ $message }}</p>@enderror
    </div>

    <div class="form-control">
      <label class="label cursor-pointer justify-start gap-3">
        <input type="checkbox" name="is_active" value="1" class="checkbox" {{ old('is_active', $product->is_active ?? true) ? 'checked' : '' }}>
        <span class="label-text">販売中にする</span>
      </label>
      @error('is_active')<p class="text-error text-sm">{{ $message }}</p>@enderror
    </div>

    @if($product->exists)
      <div class="mt-3">
        <p class="text-sm opacity-70 mb-2">プレビュー</p>
        <div class="aspect-[4/3] bg-base-200 overflow-hidden rounded-lg">
          @if($product->image_url)
            <img src="{{ $product->image_url }}" alt="" class="w-full h-full object-cover">
          @else
            <div class="w-full h-full grid place-items-center">画像なし</div>
          @endif
        </div>
      </div>
    @endif
  </div>
</div>

<div class="mt-6 flex gap-3">
  <button class="btn btn-primary">保存する</button>
  @if($product->exists)
    <a href="{{ route('products.show', $product) }}" class="btn">公開ページを見る</a>
  @endif
</div>
