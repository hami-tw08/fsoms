<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1) user_id を追加
        Schema::table('reservations', function (Blueprint $table) {
            if (!Schema::hasColumn('reservations', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
            }
        });

        // 2) データ移行
        DB::statement('UPDATE reservations SET user_id = customer_id');

        // 3) 旧カラムにぶら下がる制約/索引を先に落とす（名前は調整してね）
        // うまくいかない環境もあるので try/catch で吸収
        try { Schema::table('reservations', fn (Blueprint $t) => $t->dropForeign('reservations_customer_id_foreign')); } catch (\Throwable $e) {}
        try { Schema::table('reservations', fn (Blueprint $t) => $t->dropIndex('reservations_customer_id_index')); } catch (\Throwable $e) {}
        // SQLite向けの保険（存在すれば落とす）
        try { DB::statement('DROP INDEX IF EXISTS reservations_customer_id_index'); } catch (\Throwable $e) {}

        // 4) 旧カラムを削除
        if (Schema::hasColumn('reservations', 'customer_id')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->dropColumn('customer_id');
            });
        }
    }

    public function down(): void
    {
        // 逆手順（必要なら）
        Schema::table('reservations', function (Blueprint $table) {
            if (!Schema::hasColumn('reservations', 'customer_id')) {
                $table->unsignedBigInteger('customer_id')->nullable();
            }
        });
        DB::statement('UPDATE reservations SET customer_id = user_id');
        try { DB::statement('CREATE INDEX reservations_customer_id_index ON reservations(customer_id)'); } catch (\Throwable $e) {}
        // 外部キー再作成は必要に応じて
        Schema::table('reservations', function (Blueprint $table) {
            // $table->foreign('customer_id')->references('id')->on('customers'); // 必要なら
        });
        Schema::table('reservations', function (Blueprint $table) {
            if (Schema::hasColumn('reservations', 'user_id')) {
                $table->dropColumn('user_id');
            }
        });
    }
};
