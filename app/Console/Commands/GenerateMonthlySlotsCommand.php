<?php
// app/Console/Commands/GenerateMonthlySlotsCommand.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Support\SlotRules;

class GenerateMonthlySlotsCommand extends Command
{
    protected $signature = 'slots:generate-monthly {--months=5} {--shop=1} {--from-first-of-month}';
    protected $description = '今月から指定月数分の予約枠を、ルールに沿って生成（重複はupsert）';

    public function handle(): int
    {
        $shopId   = (int)$this->option('shop');
        $months   = max(1, (int)$this->option('months'));
        $todayJp  = Carbon::now('Asia/Tokyo');

        // 生成開始日：デフォは今日、--from-first-of-month なら当月1日
        $start = $this->option('from-first-of-month')
            ? $todayJp->copy()->startOfMonth()
            : $todayJp->copy();

        // 生成終了日は start から months-1か月先の末日
        $end   = $start->copy()->addMonths($months - 1)->endOfMonth();

        $this->info("Generate slots: shop={$shopId} period={$start->toDateString()}..{$end->toDateString()}");

        $cur = $start->copy()->startOfDay();
        $count = 0;

        DB::beginTransaction();
        try {
            while ($cur->lte($end)) {
                if (SlotRules::isBusinessDay($cur)) {
                    foreach (['store','delivery'] as $type) {
                        foreach (SlotRules::ranges($type, $cur) as $range) {
                            [$st, $en] = explode('-', $range);
                            $capacity  = SlotRules::capacity($type, $range);

                            // upsert（unique: shop_id, slot_date, start_time, end_time, slot_type）
                            DB::table('reservation_slots')->updateOrInsert(
                                [
                                    'shop_id'   => $shopId,
                                    'slot_date' => $cur->toDateString(),
                                    'start_time'=> $st.':00',
                                    'end_time'  => $en.':00',
                                    'slot_type' => $type,
                                ],
                                [
                                    'capacity'        => $capacity,
                                    'is_active'       => true,
                                    'notify_threshold'=> 1,       // カラムがある想定
                                    'updated_at'      => now(),
                                    'created_at'      => now(),
                                ]
                            );
                            $count++;
                        }
                    }
                }
                $cur->addDay();
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info("done: upserted={$count}");
        return self::SUCCESS;
    }
}
