<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class ReservationSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('ja_JP');

        // ユーザーID（NOT NULL対策）
        $userId = DB::table('users')->value('id');
        if (!$userId) {
            $userId = DB::table('users')->insertGetId([
                'name'       => 'Test User',
                'email'      => 'test@example.com',
                'password'   => bcrypt('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 商品
        $products = DB::table('products')->where('is_active', 1)->get(['id','price']);
        if ($products->isEmpty()) {
            $this->command?->warn('products(is_active=1) が0件のため、予約は作成されません。');
            return;
        }

        // スロット
        $slots = DB::table('reservation_slots')
            ->where('is_active', 1)
            ->whereBetween('slot_date', [now()->subDays(14)->toDateString(), now()->addDays(90)->toDateString()])
            ->orderBy('slot_date')->orderBy('start_time')
            ->get();

        if ($slots->isEmpty()) {
            $this->command?->warn('reservation_slots が期間内に0件のため、予約は作成されません。');
            return;
        }

        // status の扱い
        $statusPlan = $this->resolveStatusPlan();

        // delivery_area カラムのメタ情報取得（NULL可/ENUM候補）
        $deliveryMeta = $this->getColumnMeta('reservations', 'delivery_area');
        $deliveryEnum = $deliveryMeta['enum'] ?? null;
        $deliveryNullable = $deliveryMeta['nullable'] ?? true;

        $inserted = 0;

        DB::transaction(function () use ($faker, $slots, $products, $userId, $statusPlan, $deliveryEnum, $deliveryNullable, &$inserted) {
            foreach ($slots as $slot) {
                $reserved = DB::table('reservations')->where('slot_id', $slot->id)->count();
                $free = max(0, (int)$slot->capacity - (int)$reserved);
                if ($free <= 0) continue;

                // 空き1でも最低1件は作る
                $toCreate = $free === 1 ? 1 : random_int(1, $free);

                for ($i = 0; $i < $toCreate; $i++) {
                    $p = $products->random();
                    $qty = random_int(1, 3);
                    $amount = ((int)$p->price) * $qty;

                    // 共通
                    $row = [
                        'slot_id'      => $slot->id,
                        'user_id'      => $userId,
                        'product_id'   => $p->id,
                        'quantity'     => $qty,
                        'total_amount' => $amount,
                        'notes'        => $faker->boolean(25) ? $faker->realText(30) : null,
                        'guest_name'   => $faker->name(),
                        'guest_phone'  => preg_replace('/[^0-9+]/', '', $faker->phoneNumber()),
                        'channel'      => $slot->slot_type, // store / delivery
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ];

                    // 配送系：スロット種別で分岐
                    if (($slot->slot_type ?? '') === 'delivery') {
                        // delivery_area は ENUM候補から選ぶ（なければ安全側で 'namie' などにフォールバック）
                        $area = $deliveryEnum && count($deliveryEnum) ? $deliveryEnum[array_rand($deliveryEnum)] : 'namie';
                        $row['delivery_area']        = $area;
                        $row['delivery_postal_code'] = $faker->postcode();
                        $row['delivery_address']     = $faker->prefecture().$faker->city().$faker->streetAddress();
                    } else {
                        // store（店頭）：NULL可なら何も入れない。NULL不可ならダミーを入れる
                        if (!$deliveryNullable) {
                            $area = $deliveryEnum && count($deliveryEnum) ? $deliveryEnum[0] : 'namie';
                            $row['delivery_area']        = $area;
                            $row['delivery_postal_code'] = '000-0000';
                            $row['delivery_address']     = '店頭受取';
                        }
                        // NULL可なら delivery_* をセットしない（DBに任せる）
                    }

                    // status の挿入方針
                    $statusValue = $this->pickStatusValue($statusPlan);
                    if ($statusValue !== null) {
                        $row['status'] = $statusValue;
                    }

                    DB::table('reservations')->insert($row);
                    $inserted++;

                    $reserved++;
                    if ($reserved >= (int)$slot->capacity) break;
                }
            }
        });

        $this->command?->info("reservations inserted: {$inserted}");
    }

    private function resolveStatusPlan(): array
    {
        $dbName = DB::getDatabaseName();
        $col = DB::selectOne("
            SELECT COLUMN_TYPE, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'reservations' AND COLUMN_NAME = 'status'
            LIMIT 1
        ", [$dbName]);

        if (!$col) {
            return ['omit' => true, 'enum' => null, 'varchar_len' => null, 'numeric' => false];
        }

        $dataType = strtolower($col->DATA_TYPE ?? '');
        $colType  = strtolower($col->COLUMN_TYPE ?? '');
        $default  = $col->COLUMN_DEFAULT;

        if ($default !== null) {
            return ['omit' => true, 'enum' => null, 'varchar_len' => null, 'numeric' => false];
        }

        if ($dataType === 'enum' && preg_match('/^enum\((.*)\)$/i', $colType, $m)) {
            $opts = array_map(function ($s) {
                return trim($s, " '\"");
            }, explode(',', $m[1]));
            return ['omit' => false, 'enum' => $opts, 'varchar_len' => null, 'numeric' => false];
        }

        if ($dataType === 'varchar' && preg_match('/varchar\((\d+)\)/i', $colType, $m)) {
            return ['omit' => false, 'enum' => null, 'varchar_len' => (int)$m[1], 'numeric' => false];
        }

        if (in_array($dataType, ['tinyint','smallint','int','bigint','decimal','float','double'], true)) {
            return ['omit' => false, 'enum' => null, 'varchar_len' => null, 'numeric' => true];
        }

        return ['omit' => true, 'enum' => null, 'varchar_len' => null, 'numeric' => false];
    }

    /** 任意カラムの NULL可/ENUM候補 を返す */
    private function getColumnMeta(string $table, string $column): array
    {
        $dbName = DB::getDatabaseName();
        $col = DB::selectOne("
            SELECT COLUMN_TYPE, DATA_TYPE, IS_NULLABLE
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
            LIMIT 1
        ", [$dbName, $table, $column]);

        if (!$col) return ['nullable' => true, 'enum' => null];

        $dataType = strtolower($col->DATA_TYPE ?? '');
        $colType  = strtolower($col->COLUMN_TYPE ?? '');
        $nullable = (strtoupper($col->IS_NULLABLE ?? 'YES') === 'YES');

        $enum = null;
        if ($dataType === 'enum' && preg_match('/^enum\((.*)\)$/i', $colType, $m)) {
            $enum = array_map(fn($s) => trim($s, " '\""), explode(',', $m[1]));
        }

        return ['nullable' => $nullable, 'enum' => $enum];
    }

    private function pickStatusValue(array $plan): ?string
    {
        if ($plan['omit'] ?? false) return null;

        $preferred = ['pending','confirmed','canceled','cancelled','new','done','paid','hold'];

        if (!empty($plan['enum'])) {
            foreach ($preferred as $p) {
                if (in_array($p, $plan['enum'], true)) return $p;
            }
            return $plan['enum'][0];
        }

        if (!empty($plan['varchar_len'])) {
            $val = 'pending';
            if (mb_strlen($val) > $plan['varchar_len']) $val = mb_substr($val, 0, $plan['varchar_len']);
            return $val;
        }

        if (!empty($plan['numeric'])) return '0';

        return null;
    }
}
