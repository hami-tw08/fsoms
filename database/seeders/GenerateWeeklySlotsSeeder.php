<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class GenerateWeeklySlotsSeeder extends Seeder
{
    public function run(): void
    {
        $shopId = 1;               // 1店舗運用
        $weeksAhead = 8;           // 何週間分作るか
        $startDate = Carbon::today()->startOfDay();
        $endDate   = $startDate->copy()->addWeeks($weeksAhead);

        // ─────────────────────────────────
        // パターン定義（曜日ごとの時間帯）
        // 曜日: 1=Mon, 2=Tue, 3=Wed, 4=Thu, 5=Fri, 6=Sat, 7=Sun
        // 水曜・金曜（仕入れ日）
        $patternWedFri = [
            ['start' => '11:00', 'end' => '12:00', 'type' => 'store',    'capacity' => 3],
            ['start' => '12:00', 'end' => '14:00', 'type' => 'delivery', 'capacity' => 2], // 配達＋昼休み
            ['start' => '14:00', 'end' => '16:00', 'type' => 'store',    'capacity' => 3],
            ['start' => '16:00', 'end' => '17:00', 'type' => 'delivery', 'capacity' => 2],
            ['start' => '17:00', 'end' => '18:30', 'type' => 'store',    'capacity' => 3],
            ['start' => '18:30', 'end' => '19:30', 'type' => 'delivery', 'capacity' => 2],
        ];

        // 木曜・土曜・日曜（仕入れなし日）
        $patternThuSatSun = [
            ['start' => '10:00', 'end' => '11:00', 'type' => 'delivery', 'capacity' => 2], // 午前中追加
            ['start' => '11:00', 'end' => '12:00', 'type' => 'store',    'capacity' => 3],
            ['start' => '12:00', 'end' => '14:00', 'type' => 'delivery', 'capacity' => 2],
            ['start' => '14:00', 'end' => '16:00', 'type' => 'store',    'capacity' => 3],
            ['start' => '16:00', 'end' => '17:00', 'type' => 'delivery', 'capacity' => 2],
            ['start' => '17:00', 'end' => '18:30', 'type' => 'store',    'capacity' => 3],
            ['start' => '18:30', 'end' => '19:30', 'type' => 'delivery', 'capacity' => 2],
        ];

        // 月曜・火曜は休み（＝枠を作らない）
        $period = CarbonPeriod::create($startDate, $endDate);
        $rows = [];

        foreach ($period as $date) {
            /** @var Carbon $date */
            $dow = (int)$date->isoWeekday(); // 1..7

            if (in_array($dow, [1,2], true)) {
                // Mon / Tue: 休み → 何もしない
                continue;
            }

            $slots = match ($dow) {
                3,5 => $patternWedFri,       // Wed, Fri
                4,6,7 => $patternThuSatSun,  // Thu, Sat, Sun
                default => [],               // 念のため
            };

            foreach ($slots as $s) {
                $rows[] = [
                    'shop_id'    => $shopId,
                    'slot_date'  => $date->toDateString(),
                    'start_time' => $s['start'] . ':00',
                    'end_time'   => $s['end']   . ':00',
                    'slot_type'  => $s['type'],        // 'store' | 'delivery'
                    'capacity'   => $s['capacity'],
                    'is_active'  => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // 既存と重複しないよう upsert（複合ユニークに合わせる）
        // 例：unique(['shop_id','slot_date','start_time','end_time','slot_type'])
        DB::table('reservation_slots')->upsert(
            $rows,
            ['shop_id','slot_date','start_time','end_time','slot_type'],
            ['capacity','is_active','updated_at']
        );
    }
}
