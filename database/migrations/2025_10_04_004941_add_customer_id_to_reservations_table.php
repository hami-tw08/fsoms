<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // customer_id が無い既存環境向けに追加する
        if (!Schema::hasColumn('reservations', 'customer_id')) {
            Schema::table('reservations', function (Blueprint $table) {
                // slot_id の直後あたりに追加（位置は任意）
                $table->unsignedBigInteger('customer_id')->nullable()->after('slot_id');
                $table->index('customer_id');
            });

            // 外部キー（customers.id）を付与
            Schema::table('reservations', function (Blueprint $table) {
                $table->foreign('customer_id')
                      ->references('id')->on('customers')
                      ->cascadeOnUpdate()
                      ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('reservations', 'customer_id')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->dropForeign(['customer_id']);
                $table->dropColumn('customer_id');
            });
        }
    }
};
