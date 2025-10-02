<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReservationSlot extends Model
{
    use HasFactory;

    // 既存互換：テーブル名を明示（変えたくなければ残してOK）
    protected $table = 'reservation_slots';

    // このテーブルに created_at / updated_at が無い構成が多いので無効化。
    // もしカラムがあるなら、true にしても構いません（動作影響なし）。
    public $timestamps = false;

    // Admin の一括更新などで mass-assignable にするカラム
    protected $fillable = [
        'shop_id',
        'slot_date',
        'start_time',
        'end_time',
        'slot_type',   // 'store' | 'delivery'
        'capacity',
        'is_active',
    ];

    // 型キャスト（読み書き双方の安全性アップ）
    protected $casts = [
        'slot_date' => 'date',     // Y-m-d
        'start_time' => 'string',  // H:i など文字列で扱う（TIME型でもOK）
        'end_time'   => 'string',
        'slot_type'  => 'string',
        'capacity'   => 'integer',
        'is_active'  => 'boolean',
        'shop_id'    => 'integer',
    ];

    public function reservations()
    {
        // reservations.slot_id → reservation_slots.id
        return $this->hasMany(Reservation::class, 'slot_id');
    }

    /* 任意：使うなら便利なスコープ（コメントアウトしてもOK）
    public function scopeDate($q, $date) { return $date ? $q->whereDate('slot_date', $date) : $q; }
    public function scopeType($q, $type) { return $type ? $q->where('slot_type', $type) : $q; }
    */
}
