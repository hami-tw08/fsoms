<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TimeSlot;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TimeSlotSeeder extends Seeder
{
    public function run(): void
    {
        // 直近 -14日 〜 +42日分のスロットを生成（水木金土日だけ）
        $start = Carbon::today()->subDays(14);
        $end   = Carbon::today()->addDays(42);

        // 来店（store）：毎時 10:00-17:00、容量5
        $storeHours = [
            ['10:00','11:00'], ['11:00','12:00'], ['12:00','13:00'],
            ['13:00','14:00'], ['14:00','15:00'], ['15:00','16:00'], ['16:00','17:00'],
        ];

        // 配達（delivery）：10-12 / 12-14 / 14-16、容量3
        $deliveryWindows = [
            ['10:00','12:00'], ['12:00','14:00'], ['14:00','16:00'],
        ];

        DB::transaction(function () use ($start, $end, $storeHours, $deliveryWindows) {
            $d = $start->copy();
            while ($d->lte($end)) {
                // 営業日は Wed(2)〜Sun(6) ※MySQL WEEKDAY基準 / Carbon: 0=Sun〜6=Sat
                // Carbon の dayOfWeek: 0(Sun)〜6(Sat)
                if (in_array($d->dayOfWeek, [3,4,5,6,0], true)) { // Wed,Thu,Fri,Sat,Sun
                    foreach ($storeHours as [$s,$e]) {
                        TimeSlot::updateOrCreate([
                            'date' => $d->toDateString(),
                            'start_time' => $s,
                            'end_time' => $e,
                            'type' => 'store',
                        ], [
                            'capacity' => 5,
                        ]);
                    }
                    foreach ($deliveryWindows as [$s,$e]) {
                        TimeSlot::updateOrCreate([
                            'date' => $d->toDateString(),
                            'start_time' => $s,
                            'end_time' => $e,
                            'type' => 'delivery',
                        ], [
                            'capacity' => 3,
                        ]);
                    }
                }
                $d->addDay();
            }
        });
    }
}
