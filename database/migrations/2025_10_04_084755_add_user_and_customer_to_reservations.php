<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            if (!Schema::hasColumn('reservations', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('slot_id');
            }
            if (!Schema::hasColumn('reservations', 'customer_id')) {
                $table->unsignedBigInteger('customer_id')->nullable()->after('user_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            if (Schema::hasColumn('reservations', 'customer_id')) {
                $table->dropColumn('customer_id');
            }
            if (Schema::hasColumn('reservations', 'user_id')) {
                $table->dropColumn('user_id');
            }
        });
    }
};
