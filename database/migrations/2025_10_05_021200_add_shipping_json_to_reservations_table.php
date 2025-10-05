<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            // お客さま入力の詳細を丸ごと保存するJSON
            // 例：orderer_name, orderer_phone, recipient_name, company, store, area(ja), postal_code, address, notes など
            $table->json('shipping_json')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('shipping_json');
        });
    }
};
