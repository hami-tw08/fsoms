<?php
// app/Support/SlotRules.php
namespace App\Support;

use Carbon\Carbon;

final class SlotRules
{
    // 営業日: 水(3) 木(4) 金(5) 土(6) 日(7) ※ Carbon: 1=Mon ... 7=Sun
    public static function isBusinessDay(Carbon $d): bool
    {
        $dow = (int)$d->isoWeekday(); // 1..7
        return in_array($dow, [3,4,5,6,7], true);
    }

    // カテゴリ別：曜日グループ
    public static function dayKey(Carbon $d): ?string
    {
        $dow = (int)$d->isoWeekday();
        if (in_array($dow, [3,5], true))      return 'WED_FRI';
        if (in_array($dow, [4,6,7], true))    return 'THU_SAT_SUN';
        return null;
    }

    // フロントJSと一致させる
    public static function ranges(string $type, Carbon $d): array
    {
        $key = self::dayKey($d);
        if (!$key) return [];
        $RULES = [
            'delivery' => [
                'WED_FRI'     => ["12:00-14:00","16:00-17:00","18:30-19:30"],
                'THU_SAT_SUN' => ["10:00-11:00","12:00-14:00","16:00-17:00","18:30-19:30"],
            ],
            'store' => [
                'WED_FRI'     => ["14:00-16:00","17:00-18:30"],
                'THU_SAT_SUN' => ["11:00-12:00","14:00-16:00","17:00-18:30"],
            ],
        ];
        return $RULES[$type][$key] ?? [];
    }

    // 収容数：フロントJSと一致
    public static function capacity(string $type, string $range): int
    {
        if ($type === 'store') return 3;
        // delivery
        return $range === '12:00-14:00' ? 3 : 1;
    }
}
