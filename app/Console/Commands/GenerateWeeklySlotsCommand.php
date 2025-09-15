<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class GenerateWeeklySlotsCommand extends Command
{
    protected $signature = 'slots:generate {--weeks=8}';
    protected $description = '曜日パターンに基づいて未来N週間分の予約枠を補充（3日前未満は作らない／重複はupsert）';

    public function handle(): int
    {
        // ─────────────────────────────────────────────────────────
        // shops のレコード取得（無ければ作成）
        // reservation_lead_days は DBの値を尊重しつつ、最低でも 3 に補正
        // ─────────────────────────────────────────────────────────
        $shop = DB::table('shops')->first();

        if (!$shop) {
            $shopId = DB::table('shops')->insertGetId([
                'name' => 'Namie Flower',
                'business_days' => json_encode(['Wed','Thu','Fri','Sat','Sun']),
                'reservation_lead_days' => 3, // デフォ 3日リード
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $leadDays = 3;
        } else {
            $shopId   = $shop->id;
            $leadDays = max(3, (int)($shop->reservation_lead_days ?? 0)); // 最低3日に矯正
        }

        $weeksAhead = (int)$this->option('weeks'); // 未来何週間分まで用意するか

        // 3日前未満は作らない（今日+leadDays を起点にする）
        $minDate  = Carbon::today()->addDays($leadDays)->startOfDay();
        $endDate  = Carbon::today()->addWeeks($weeksAhead)->endOfDay();

        // ── パターン定義 ──────────────────
        // Wed & Fri（仕入れ日）
        $patternWedFri = [
            ['start'=>'11:00','end'=>'12:00','type'=>'store',    'capacity'=>3],
            ['start'=>'12:00','end'=>'14:00','type'=>'delivery', 'capacity'=>2],
            ['start'=>'14:00','end'=>'16:00','type'=>'store',    'capacity'=>3],
            ['start'=>'16:00','end'=>'17:00','type'=>'delivery', 'capacity'=>2],
            ['start'=>'17:00','end'=>'18:30','type'=>'store',    'capacity'=>3],
            ['start'=>'18:30','end'=>'19:30','type'=>'delivery', 'capacity'=>2],
        ];
        // Thu, Sat, Sun（仕入れなし日）
        $patternThuSatSun = [
            ['start'=>'10:00','end'=>'11:00','type'=>'delivery', 'capacity'=>2],
            ['start'=>'11:00','end'=>'12:00','type'=>'store',    'capacity'=>3],
            ['start'=>'12:00','end'=>'14:00','type'=>'delivery', 'capacity'=>2],
            ['start'=>'14:00','end'=>'16:00','type'=>'store',    'capacity'=>3],
            ['start'=>'16:00','end'=>'17:00','type'=>'delivery', 'capacity'=>2],
            ['start'=>'17:00','end'=>'18:30','type'=>'store',    'capacity'=>3],
            ['start'=>'18:30','end'=>'19:30','type'=>'delivery', 'capacity'=>2],
        ];

        // ── 生成ループ（Mon/Tue はスキップ） ──────────────────
        $period = CarbonPeriod::create($minDate->copy()->startOfDay(), $endDate->copy()->startOfDay());
        $rows   = [];

        foreach ($period as $date) {
            /** @var Carbon $date */
            $dow = (int)$date->isoWeekday(); // 1=Mon ... 7=Sun
            if (in_array($dow, [1,2], true)) {
                continue; // Mon/Tue: 休み
            }

            $slots = match ($dow) {
                3,5   => $patternWedFri,      // Wed & Fri
                4,6,7 => $patternThuSatSun,   // Thu, Sat, Sun
                default => [],
            };

            foreach ($slots as $s) {
                $rows[] = [
                    'shop_id'    => $shopId,
                    'slot_date'  => $date->toDateString(),
                    'start_time' => $s['start'].':00',
                    'end_time'   => $s['end'].':00',
                    'slot_type'  => $s['type'],
                    'capacity'   => $s['capacity'],
                    'is_active'  => true, // 3日前未満は生成自体しないので常にtrueでOK
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // ── upsert（ユニークキー相当の複合キーで冪等化） ──────────────────
        $count = count($rows);
        if ($count > 0) {
            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('reservation_slots')->upsert(
                    $chunk,
                    ['shop_id','slot_date','start_time','end_time','slot_type'],
                    ['capacity','is_active','updated_at']
                );
            }
        }

        $this->info(sprintf(
            'Generated/updated %d slots from %s to %s (weeks=%d, leadDays=%d)',
            $count,
            $minDate->toDateString(),
            $endDate->toDateString(),
            $weeksAhead,
            $leadDays
        ));

        return Command::SUCCESS;
    }
}
