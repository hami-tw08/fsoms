<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            // すでに同名カラムがある場合はスキップ（重複エラー回避）
            if (!Schema::hasColumn('reservations', 'guest_name')) {
                $table->string('guest_name')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('reservations', 'guest_phone')) {
                $table->string('guest_phone')->nullable()->after('guest_name');
            }
            if (!Schema::hasColumn('reservations', 'channel')) {
                $table->string('channel')->default('store')->after('status'); // store/delivery
            }
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            if (Schema::hasColumn('reservations', 'guest_name')) {
                $table->dropColumn('guest_name');
            }
            if (Schema::hasColumn('reservations', 'guest_phone')) {
                $table->dropColumn('guest_phone');
            }
            if (Schema::hasColumn('reservations', 'channel')) {
                $table->dropColumn('channel');
            }
        });
    }
};
