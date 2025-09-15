<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'price',
        'image_url',   // 外部URL用（任意）
        'image_path',  // アップロード保存用（今回追加）
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'integer',
    ];

    // ルートモデルバインディングを slug に
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // ¥フォーマット
    protected function priceFormatted(): Attribute
    {
        return Attribute::get(fn () => number_format((int)($this->price ?? 0)) . '円');
    }

    /**
     * image_url アクセサ
     * - image_path があれば Storage の公開URLを返す
     * - なければDBの image_url（外部URL）をそのまま返す
     * これで Blade は $product->image_url のままでOK
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(function ($value, $attributes) {
            if (!empty($attributes['image_path'])) {
                return Storage::disk('public')->url($attributes['image_path']);
            }
            return $value ?: null;
        });
    }

    // slug 自動生成（重複回避）
    protected static function booted(): void
    {
        static::saving(function (Product $product) {
            if (empty($product->slug)) {
                $base = Str::slug($product->name ?? '');
                $slug = $base ?: Str::random(8);
                $i = 1;
                while (
                    static::where('slug', $slug)
                        ->when($product->exists, fn ($q) => $q->where('id', '!=', $product->id))
                        ->exists()
                ) {
                    $slug = $base . '-' . $i++;
                }
                $product->slug = $slug;
            }
        });
    }
}
