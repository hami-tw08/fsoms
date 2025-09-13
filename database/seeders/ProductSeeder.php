<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'name' => '商品A：季節のブーケ（S）',
                'slug' => 'bouquet-s',
                'price' => 3300,
                'image_url' => 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?q=80&w=1280&auto=format&fit=crop',
                'description' => '手土産にぴったりのSサイズ。色味はおまかせ可。',
                'is_active' => true,
            ],
            [
                'name' => '商品B：季節のブーケ（M）',
                'slug' => 'bouquet-m',
                'price' => 5500,
                'image_url' => 'https://images.unsplash.com/photo-1501004318641-b39e6451bec6?q=80&w=1280&auto=format&fit=crop',
                'description' => '一番人気のMサイズ。誕生日・記念日に。',
                'is_active' => true,
            ],
            [
                'name' => '商品C：アレンジメント（Box）',
                'slug' => 'arrangement-box',
                'price' => 7700,
                'image_url' => 'https://images.unsplash.com/photo-1492889971304-ac16ab4a4a31?q=80&w=1280&auto=format&fit=crop',
                'description' => '器付きでそのまま飾れます。お供えにも対応。',
                'is_active' => true,
            ],
            [
                'name' => '花束（和風）',
                'slug' => 'wa-bouquet',
                'price' => 4400,
                'image_url' => 'https://images.unsplash.com/photo-1504198458649-3128b932f49b?q=80&w=1280&auto=format&fit=crop',
                'description' => '和の色合いを活かした落ち着いた花束。',
                'is_active' => true,
            ],
            [
                'name' => '供花（白）',
                'slug' => 'memorial-white',
                'price' => 6600,
                'image_url' => 'https://images.unsplash.com/photo-1526045612212-70caf35c14df?q=80&w=1280&auto=format&fit=crop',
                'description' => '白基調のお供え用アレンジメント。',
                'is_active' => true,
            ],
        ];

        foreach ($items as $i) {
            Product::updateOrCreate(['slug' => $i['slug']], $i);
        }

        // ★ ダミー商品を追加生成（季節限定などを想定）
        for ($n = 1; $n <= 10; $n++) {
            $slug = "seasonal-{$n}";
            Product::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => "季節のおすすめ {$n}",
                    'slug' => $slug,
                    'description' => "季節の花材を使ったおまかせ {$n}",
                    'price' => rand(3000, 9000),
                    'image_url' => "https://placehold.co/600x400?text=Seasonal+{$n}",
                    'is_active' => (bool)rand(0, 1),
                ]
            );
        }
    }
}
