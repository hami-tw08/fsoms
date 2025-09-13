<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReservationSlotSeeder extends Seeder
{
    public function run(): void
    {
        // 期間：過去14日〜未来42日（水木金土日だけ）
        $start = Carbon::today()->subDays(14);
        $end   = Carbon::today()->addDays(42);

        // 来店（store）：毎時 10:00-17:00 cap=5
        $storeHours = [
            ['10:00','11:00'], ['11:00','12:00'], ['12:00','13:00'],
            ['13:00','14:00'], ['14:00','15:00'], ['15:00','16:00'], ['16:00','17:00'],
        ];
        // 配達（delivery）：10-12 / 12-14 / 14-16 cap=3
        $deliveryWindows = [
            ['10:00','12:00'], ['12:00','14:00'], ['14:00','16:00'],
        ];

        // 既存のショップIDがあれば使う（無ければ null にしておく）
        $shopId = DB::table('shops')->value('id');

        DB::transaction(function () use ($start, $end, $storeHours, $deliveryWindows, $shopId) {
            $d = $start->copy();
            while ($d->lte($end)) {
                // 営業日：水木金土日（Carbon: 0=Sun, 3=Wed,4=Thu,5=Fri,6=Sat）
                if (in_array($d->dayOfWeek, [3,4,5,6,0], true)) {
                    // store 枠
                    foreach ($storeHours as [$s,$e]) {
                        DB::table('reservation_slots')->updateOrInsert(
                            [
                                'slot_date'  => $d->toDateString(),
                                'start_time' => $s,
                                'end_time'   => $e,
                                'slot_type'  => 'store',
                                'shop_id'    => $shopId, // null可ならそのままnullでもOK
                            ],
                            [
                                'capacity'  => 5,
                                'is_active' => 1,
                                'updated_at'=> now(),
                                'created_at'=> now(),
                            ]
                        );
                    }
                    // delivery 枠
                    foreach ($deliveryWindows as [$s,$e]) {
                        DB::table('reservation_slots')->updateOrInsert(
                            [
                                'slot_date'  => $d->toDateString(),
                                'start_time' => $s,
                                'end_time'   => $e,
                                'slot_type'  => 'delivery',
                                'shop_id'    => $shopId,
                            ],
                            [
                                'capacity'  => 3,
                                'is_active' => 1,
                                'updated_at'=> now(),
                                'created_at'=> now(),
                            ]
                        );
                    }
                }
                $d->addDay();
            }
        });
    }
}
