<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // --- 先にテストユーザーを用意（NOT NULL対策）---
        $user = \App\Models\User::firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => bcrypt('password')]
        );

        // shops がある & 空なら1件だけ作成（外部キー対策）
        if (Schema::hasTable('shops') && DB::table('shops')->count() === 0) {
            DB::table('shops')->insert([
                'name'       => 'Main Shop',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // ---- シーダー呼び出し順 ----
        $calls = [
            ProductSeeder::class,
            ReservationSlotSeeder::class,
            ReservationSeeder::class,
        ];
        if (class_exists(\Database\Seeders\DeliveryAreaSeeder::class)) {
            array_splice($calls, 1, 0, [DeliveryAreaSeeder::class]);
        }

        $this->call($calls);
    }
}
