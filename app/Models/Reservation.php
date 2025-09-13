<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Reservation extends Model
{
    use HasFactory;

    /** 予約テーブルの書き込み許可カラム（必要に応じて調整） */
    protected $fillable = [
        'slot_id',
        'user_id',
        'product_id',      // Lean: 予約=1商品運用の場合のみ
        'quantity',
        'total_amount',
        'status',
        'notes',

        // 配達先を reservations に持つ設計にしている場合のみ
        'delivery_area',
        'delivery_postal_code',
        'delivery_address',

        // ★ 追加：ゲスト予約（会員でなくても可）
        'guest_name',
        'guest_phone',

        // ★ 追加：来店(store) / 配達(delivery) のチャネル
        'channel',
    ];

    /** 便利キャスト（任意） */
    protected $casts = [
        'quantity'     => 'integer',
        'total_amount' => 'decimal:2',
    ];

    /** 想定ステータス（必要ならDBやEnumに寄せてもOK） */
    public const STATUSES = ['pending','confirmed','fulfilled','canceled'];

    /** チャネル種別 */
    public const CHANNEL_STORE    = 'store';
    public const CHANNEL_DELIVERY = 'delivery';

    /**
     * ユーザー（顧客）
     * reservations.user_id → users.id
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * 予約枠
     * reservations.slot_id → reservation_slots.id
     */
    public function slot(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ReservationSlot::class, 'slot_id');
    }

    /**
     * 商品（Lean: 予約=1商品のとき）
     * reservations.product_id → products.id
     * 将来 multi-item にする場合は、この関連は使わず items 経由に切替。
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Product::class);
    }

    /**
     * 明細（任意／reservation_items を使う場合のみ）
     * 使わないならこの関連は無視してOK
     */
    public function items(): HasMany
    {
        return $this->hasMany(\App\Models\ReservationItem::class);
    }

    /* =========================================================
     |  アクセサ / ミューテタ / スコープ（今回追加分）
     ========================================================= */

    /** 合計金額（円表記） */
    public function getTotalAmountFormattedAttribute(): string
    {
        // total_amount は decimal(?,2) 想定。整数円なら小数部は落とす
        $val = (float) $this->total_amount;
        if (abs($val - round($val)) < 0.001) {
            return number_format((int) round($val)) . '円';
        }
        return number_format($val, 2) . '円';
    }

    /** 来店 or 配達の真偽アクセサ */
    public function getIsStoreAttribute(): bool
    {
        return $this->channel === self::CHANNEL_STORE;
    }

    public function getIsDeliveryAttribute(): bool
    {
        return $this->channel === self::CHANNEL_DELIVERY;
    }

    /** 電話番号の軽い正規化（数字と+のみ残す） */
    public function setGuestPhoneAttribute($value): void
    {
        if (is_string($value)) {
            $this->attributes['guest_phone'] = preg_replace('/[^0-9+]/', '', $value) ?? $value;
        } else {
            $this->attributes['guest_phone'] = $value;
        }
    }

    /** ステータスの正規化（未定義ならそのまま通すが、小文字化） */
    public function setStatusAttribute($value): void
    {
        $this->attributes['status'] = is_string($value) ? strtolower($value) : $value;
    }

    /** チャネルの正規化（小文字化） */
    public function setChannelAttribute($value): void
    {
        $this->attributes['channel'] = is_string($value) ? strtolower($value) : $value;
    }

    /* =========================
     |  クエリスコープ
     ========================= */

    /** ステータスで絞り込み */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', strtolower($status));
    }

    /** チャネルで絞り込み */
    public function scopeChannel($query, string $channel)
    {
        return $query->where('channel', strtolower($channel));
    }

    /** 期間で絞り込み（予約枠の開始日ベース） */
    public function scopeBetweenDates($query, string $from, string $to)
    {
        return $query->whereHas('slot', function ($q) use ($from, $to) {
            $q->whereBetween('start_at', [$from, $to]); // reservation_slots.start_at を想定
        });
    }
}
