<?php
// database/migrations/XXXX_XX_XX_XXXXXX_add_unique_idx_to_reservation_slots.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('reservation_slots', function (Blueprint $table) {
            // shop単位・日付・時間・種別で一意
            $table->unique(['shop_id','slot_date','start_time','end_time','slot_type'],
                'uq_shop_date_time_type');
        });
    }
    public function down(): void {
        Schema::table('reservation_slots', function (Blueprint $table) {
            $table->dropUnique('uq_shop_date_time_type');
        });
    }
};
