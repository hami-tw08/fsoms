<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
             // 旧FKがあれば外す（名前は環境差あるので配列版でOK）
            try { $table->dropForeign(['customer_id']); } catch (\Throwable $e) {}

            // 旧カラムを削除（存在チェックして安全に）
            if (Schema::hasColumn('reservations', 'customer_id')) {
                $table->dropColumn('customer_id');
            }

            // 新カラム & FK 追加
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
             // 逆順：user_id を外して customer_id を戻す（最低限の復元）
            try { $table->dropForeign(['user_id']); } catch (\Throwable $e) {}
            $table->dropColumn('user_id');

            $table->foreignId('customer_id')
                ->constrained('customers')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }
};
