<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 既に user_id がある場合は「NULL 許可」に変更
        if (Schema::hasColumn('reservations', 'user_id')) {
            // change() を使うために doctrine/dbal が必要です（下記コマンド参照）
            Schema::table('reservations', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->change();
            });
        } else {
            // 無い環境向け：user_id を nullable で追加（外部キーは任意）
            Schema::table('reservations', function (Blueprint $table) {
                $table->foreignId('user_id')
                      ->nullable()
                      ->constrained('users')
                      ->cascadeOnUpdate()
                      ->nullOnDelete()
                      ->after('slot_id');
            });
        }
    }

    public function down(): void
    {
        // 元に戻すのは危険（NULL→NOT NULL）なので何もしません
    }
};
