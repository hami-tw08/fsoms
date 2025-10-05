<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Reservation extends Model
{
    /**
     * 配送エリアの許可リスト（要件：浪江/双葉/大熊/小高区）
     */
    public const DELIVERY_AREAS = ['浪江', '双葉', '大熊', '小高区'];

    /**
     * mass assignable（注文型＋スロット直結型の両対応）
     */
    protected $fillable = [
        // ── 注文（items/pivot）型のフィールド ──
        'order_code',
        'method',        // store|delivery
        'receive_date',  // Y-m-d
        'receive_time',  // H:i
        'total',         // 注文型の合計（null のときは items 合算）
        'status',        // pending|confirmed|completed|canceled 等
        'orderer_name',
        'orderer_phone',
        'recipient_name',
        'recipient_company',
        'recipient_store',
        'area',          // 配送エリア（注文型）
        'address',

        // ── スロット直結型で使うフィールド（Controller側の create() 相当で利用）──
        'slot_id',
        'user_id',
        'customer_id',   // ★ 追記：顧客リレーション用
        'product_id',
        'quantity',
        'total_amount',           // スロット型の合計
        'notes',
        'delivery_area',          // 配送エリア（スロット型）
        'delivery_postal_code',
        'delivery_address',
        'guest_name',
        'guest_phone',
        // ★ 追記：shipping_json を保存できるように（DBにカラムがある前提）
        'shipping_json',
    ];

    protected $casts = [
        'receive_date' => 'date',
        'receive_time' => 'string',
        'quantity'     => 'integer',
        'total_amount' => 'integer',
        'shipping_json' => 'array',
    ];

    /**
     * API/配列化時に自動で含める仮想属性
     */
    protected $appends = [
        'total_amount_normalized',
        'receive_at',
        'display_name',
    ];

    /* =========================
     |  リレーション
     * =======================*/

    /**
     * 明細（1予約 : 多明細） … 注文型で使用
     */
    public function items(): HasMany
    {
        return $this->hasMany(ReservationItem::class);
    }

    /**
     * 商品（多対多：reservation_items 経由） … 注文型で使用
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'reservation_items')
            ->withPivot(['quantity', 'unit_price'])
            ->withTimestamps();
    }

    /**
     * スロット（reservations.slot_id → reservation_slots.id） … スロット直結型で使用
     */
    public function slot(): BelongsTo
    {
        return $this->belongsTo(ReservationSlot::class, 'slot_id');
    }

    /**
     * 単一商品（スロット直結型の予約で使用）
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * 予約ユーザー（ゲスト予約時はnull）
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * ★ 追記：顧客（customers）リレーション
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /* =========================
     |  アクセサ
     * =======================*/

    /**
     * 表示用氏名（今回は予約者＝orderer を優先）
     */
    public function getDisplayNameAttribute(): ?string
    {
        return $this->orderer_name ?: null;
    }

    /**
     * 正規化した合計金額
     * - 注文型: total（nullなら items 合算）
     * - スロット型: total_amount を優先
     */
    public function getTotalAmountNormalizedAttribute(): int
    {
        // スロット型の total_amount を最優先
        if (!is_null($this->attributes['total_amount'] ?? null)) {
            return (int) $this->attributes['total_amount'];
        }

        // 注文型の total があればそれ
        if (!is_null($this->attributes['total'] ?? null)) {
            return (int) $this->attributes['total'];
        }

        // 注文型の items 合算（リレーションの有無で分岐）
        if ($this->relationLoaded('items')) {
            return (int) $this->items->sum(function ($i) {
                return (int) ($i->quantity ?? 0) * (int) ($i->unit_price ?? 0);
            });
        }

        return (int) $this->items()
            ->selectRaw('COALESCE(SUM(quantity * unit_price), 0) as sum_total')
            ->value('sum_total');
    }

    /**
     * 受取の「日時」アクセサ（Carbon）
     */
    public function getReceiveAtAttribute(): ?Carbon
    {
        if (!$this->receive_date) {
            return null;
        }
        $date = $this->receive_date instanceof Carbon
            ? $this->receive_date->format('Y-m-d')
            : (string) $this->receive_date;

        $time = $this->receive_time ?: '00:00';

        return Carbon::parse("{$date} {$time}", config('app.timezone'));
    }

    /* =========================
     |  検索スコープ（注文型向け）
     * =======================*/

    public function scopeMethod(Builder $q, ?string $method): Builder
    {
        return $method ? $q->where('method', $method) : $q;
    }

    public function scopeArea(Builder $q, ?string $area): Builder
    {
        return $area ? $q->where('area', $area) : $q;
    }

    public function scopeReceiveFrom(Builder $q, ?string $from): Builder
    {
        return $from ? $q->whereDate('receive_date', '>=', $from) : $q;
    }

    public function scopeReceiveTo(Builder $q, ?string $to): Builder
    {
        return $to ? $q->whereDate('receive_date', '<=', $to) : $q;
    }

    public function scopeKeyword(Builder $q, ?string $keyword): Builder
    {
        if (!$keyword) return $q;

        $like = '%' . $keyword . '%';
        return $q->where(function (Builder $w) use ($like, $keyword) {
            $w->where('orderer_name', 'like', $like)
              ->orWhere('orderer_phone', 'like', $like)
              ->orWhere('order_code', 'like', $like)
              ->orWhere('recipient_name', 'like', $like)
              ->orWhere('address', 'like', $like)
              ->orWhere('area', 'like', $like);

            if (ctype_digit($keyword)) {
                $w->orWhere('id', (int) $keyword);
            }
        });
    }
}
