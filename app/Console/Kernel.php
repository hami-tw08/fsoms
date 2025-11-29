<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // ─────────────────────────────────────────────
        // 月次: 毎月1日の 00:10（日本時間）に5か月先まで生成
        // ─────────────────────────────────────────────
        $schedule->command('slots:generate-monthly --months=5 --shop=1 --from-first-of-month')
            ->monthlyOn(1, '0:10')
            ->timezone('Asia/Tokyo')
            ->withoutOverlapping()
            ->onOneServer();

        // 週次の旧タスクは廃止（下行のような daily/schedule は置かない）
        // $schedule->command('slots:generate --weeks=8')->dailyAt('02:00'); // ← 削除
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
