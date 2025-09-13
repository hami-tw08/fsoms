<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name','slug','description','price','image_url','is_active',
    ];

    // ルートモデルバインディングに slug を使う
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // 表示用フォーマット
    public function getPriceFormattedAttribute(): string
    {
        return number_format($this->price) . '円';
    }
}
