<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        // 認可は別のミドルウェアで管理する想定
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required','string','max:255'],
            'slug'        => ['nullable','string','max:255'], // 空なら自動生成
            'description' => ['nullable','string'],
            'price'       => ['required','integer','min:0'],
            'image'       => ['nullable','image','max:5120'], // 5MB
            'image_url'   => ['nullable','url'],
            'is_active'   => ['sometimes','boolean'],
        ];
    }
}
