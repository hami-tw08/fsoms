<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('products')) {
            return;
        }
        
        Schema::create('products', function (Blueprint $table) {
            $table->id();                                // 主キー
            $table->string('name');                      // 商品名
            $table->string('slug')->unique();            // URL用slug
            $table->text('description')->nullable();     // 説明文
            $table->unsignedInteger('price');            // 税込価格（円）
            $table->string('image_url')->nullable();     // 画像URL
            $table->boolean('is_active')->default(true); // 販売中フラグ
            $table->timestamps();                        // created_at / updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
