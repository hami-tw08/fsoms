<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

        // 商品 / スロット
        $products = DB::table('products')->where('is_active', 1)->get(['id','price']);
        if ($products->isEmpty()) return;

        $slots = DB::table('reservation_slots')
            ->where('is_active', 1)
            ->whereBetween('slot_date', [now()->subDays(7)->toDateString(), now()->addDays(28)->toDateString()])
            ->orderBy('slot_date')->orderBy('start_time')
            ->get();

        // status カラムの取り扱い方を決定
        $statusPlan = $this->resolveStatusPlan();

        DB::transaction(function () use ($faker, $slots, $products, $userId, $statusPlan) {
            foreach ($slots as $slot) {
                $reserved = DB::table('reservations')->where('slot_id', $slot->id)->count();
                $free = max(0, (int)$slot->capacity - (int)$reserved);
                if ($free <= 0) continue;

                $toCreate = random_int(0, max(0, $free - 1));
                for ($i = 0; $i < $toCreate; $i++) {
                    $p = $products->random();
                    $qty = random_int(1, 3);
                    $amount = ((int)$p->price) * $qty;

                    $row = [
                        'slot_id'          => $slot->id,
                        'user_id'          => $userId,
                        'product_id'       => $p->id,
                        'quantity'         => $qty,
                        'total_amount'     => $amount,
                        'notes'            => $faker->boolean(25) ? $faker->realText(30) : null,

                        // 配達先系：NOT NULL でも落ちないよう空文字で埋める
                        'delivery_area'        => '',
                        'delivery_postal_code' => '',
                        'delivery_address'     => '',

                        'guest_name'       => $faker->name(),
                        'guest_phone'      => preg_replace('/[^0-9+]/', '', $faker->phoneNumber()),
                        'channel'          => $slot->slot_type, // store / delivery
                        'created_at'       => now(),
                        'updated_at'       => now(),
                    ];

                    // status の挿入方針
                    $statusValue = $this->pickStatusValue($statusPlan);
                    if ($statusValue !== null) {
                        $row['status'] = $statusValue; // デフォルト使用時は null を返して未設定にする
                    }

                    DB::table('reservations')->insert($row);

                    $reserved++;
                    if ($reserved >= (int)$slot->capacity) break;
                }
            }
        });
    }

    /**
     * statusカラムの型・デフォルト・NULL可を検出して挿入方針を返す
     * 返り値: [
     *   'omit' => bool,           // trueなら挿入しない（DBデフォルトに任せる）
     *   'enum' => string[]|null,  // ENUM候補（あれば）
     *   'varchar_len' => int|null,// VARCHAR長
     *   'numeric' => bool,        // 数値型か
     * ]
     */
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
            // カラムが無いなら挿入しない
            return ['omit' => true, 'enum' => null, 'varchar_len' => null, 'numeric' => false];
        }

        $dataType = strtolower($col->DATA_TYPE ?? '');
        $colType  = strtolower($col->COLUMN_TYPE ?? '');
        $default  = $col->COLUMN_DEFAULT;
        $nullable = (strtoupper($col->IS_NULLABLE ?? 'YES') === 'YES');

        // デフォルトがあるなら、挿入せずDBに任せるのが安全
        if ($default !== null) {
            return ['omit' => true, 'enum' => null, 'varchar_len' => null, 'numeric' => false];
        }

        // ENUM
        if ($dataType === 'enum' && preg_match('/^enum\((.*)\)$/i', $colType, $m)) {
            // 'val','val2'... を配列に
            $opts = array_map(function ($s) {
                return trim($s, " '\"");
            }, explode(',', $m[1]));
            return ['omit' => false, 'enum' => $opts, 'varchar_len' => null, 'numeric' => false];
        }

        // 文字列（varcharなど）
        if ($dataType === 'varchar' && preg_match('/varchar\((\d+)\)/i', $colType, $m)) {
            return ['omit' => false, 'enum' => null, 'varchar_len' => (int)$m[1], 'numeric' => false];
        }

        // 数値型
        if (in_array($dataType, ['tinyint','smallint','int','bigint','decimal','float','double'], true)) {
            return ['omit' => false, 'enum' => null, 'varchar_len' => null, 'numeric' => true];
        }

        // よくわからない型 → 挿入しないでDBに任せる（NOT NULL/デフォルト無しなら後で修正が必要）
        return ['omit' => true, 'enum' => null, 'varchar_len' => null, 'numeric' => false];
    }

    /**
     * planに応じて挿入するstatus値を返す。挿入しない場合は null を返す。
     */
    private function pickStatusValue(array $plan): ?string
    {
        if ($plan['omit'] ?? false) {
            return null; // デフォルト使用
        }

        // 優先候補
        $preferred = ['pending','confirmed','canceled','cancelled','new','done','paid','hold'];

        // ENUM候補から選ぶ
        if (!empty($plan['enum'])) {
            foreach ($preferred as $p) {
                if (in_array($p, $plan['enum'], true)) {
                    return $p;
                }
            }
            return $plan['enum'][0]; // どれも無ければ先頭
        }

        // 文字列長がある場合は切り詰め
        if (!empty($plan['varchar_len'])) {
            $val = 'pending';
            if (mb_strlen($val) > $plan['varchar_len']) {
                $val = mb_substr($val, 0, $plan['varchar_len']);
            }
            return $val;
        }

        // 数値型なら 0 にする
        if (!empty($plan['numeric'])) {
            return '0';
        }

        // 不明なら挿入しない（デフォルトに任せる）
        return null;
    }
}
