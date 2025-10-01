<?php
// database/migrations/2025_10_01_132006_add_notify_fields_to_slots_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private string $table;

    public function __construct()
    {
        // 実在テーブルを自動判定（reservation_slots 優先）
        if (Schema::hasTable('reservation_slots')) {
            $this->table = 'reservation_slots';
        } elseif (Schema::hasTable('slots')) {
            $this->table = 'slots';
        } elseif (Schema::hasTable('time_slots')) {
            $this->table = 'time_slots';
        } else {
            $this->table = ''; // 見つからなければ後続で no-op
        }
    }

    public function up(): void
    {
        if ($this->table === '') return;

        Schema::table($this->table, function (Blueprint $table) {
            // notify_threshold
            if (!Schema::hasColumn($this->table, 'notify_threshold')) {
                $col = $table->unsignedTinyInteger('notify_threshold')->default(1);
                // remaining がある時だけ after を付ける
                if (Schema::hasColumn($this->table, 'remaining')) {
                    $col->after('remaining');
                }
            }

            // notified_low_at
            if (!Schema::hasColumn($this->table, 'notified_low_at')) {
                $col2 = $table->timestamp('notified_low_at')->nullable();
                if (Schema::hasColumn($this->table, 'notify_threshold')) {
                    $col2->after('notify_threshold');
                }
            }
        });
    }

    public function down(): void
    {
        if ($this->table === '') return;

        Schema::table($this->table, function (Blueprint $table) {
            if (Schema::hasColumn($this->table, 'notified_low_at')) {
                $table->dropColumn('notified_low_at');
            }
            if (Schema::hasColumn($this->table, 'notify_threshold')) {
                $table->dropColumn('notify_threshold');
            }
        });
    }
};
