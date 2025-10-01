<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

class Reservation extends Model
{
    /**
     * 配送エリアの許可リスト（要件：浪江/双葉/大熊/小高区）
     */
    public const DELIVERY_AREAS = ['浪江', '双葉', '大熊', '小高区'];

    protected $fillable = [
        'order_code',
        'method',        // store|delivery
        'receive_date',  // Y-m-d
        'receive_time',  // H:i
        'total',
        'status',        // pending|confirmed など
        // 注文者
        'orderer_name',
        'orderer_phone',
        // 配送時のみ
        'recipient_name',
        'recipient_company',
        'recipient_store',
        'area',          // 配送エリア
        'address',
    ];

    protected $casts = [
        'receive_date' => 'date',
        'receive_time' => 'string',
    ];

    /**
     * API/配列化時に自動で含める仮想属性
     */
    protected $appends = [
        'total_amount',
        'receive_at',
        'display_name',
    ];

    /**
     * 明細（1予約 : 多明細）
     */
    public function items(): HasMany
    {
        return $this->hasMany(ReservationItem::class);
    }

    /**
     * 商品（多対多：reservation_items 経由）
     * ->withPivot で数量/単価が取れるので集計UIで便利
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'reservation_items')
            ->withPivot(['quantity', 'unit_price'])
            ->withTimestamps();
    }

    /**
     * 表示用氏名（今回は予約者＝orderer を優先）
     */
    public function getDisplayNameAttribute(): ?string
    {
        return $this->orderer_name ?: null;
    }

    /**
     * 合計金額（totalがnullなら明細合算）
     */
    public function getTotalAmountAttribute(): int
    {
        if (!is_null($this->attributes['total'] ?? null)) {
            return (int) $this->attributes['total'];
        }
        // 明細から計算（quantity * unit_price の合計）
        if ($this->relationLoaded('items')) {
            return (int) $this->items->sum(function ($i) {
                return (int) ($i->quantity ?? 0) * (int) ($i->unit_price ?? 0);
            });
        }
        // 未ロード時はDB集計
        return (int) $this->items()
            ->selectRaw('COALESCE(SUM(quantity * unit_price), 0) as sum_total')
            ->value('sum_total');
    }

    /**
     * 受取の「日時」アクセサ（Carbon）
     * - dateのみ設定でtime空なら 00:00 扱い
     * - dateが無ければ null
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

    /* =========================================================
     |  検索スコープ（コントローラのフィルタで使えるように）
     * =======================================================*/

    /** method絞り込み（store / delivery） */
    public function scopeMethod(Builder $q, ?string $method): Builder
    {
        return $method ? $q->where('method', $method) : $q;
    }

    /** area絞り込み（配送エリア） */
    public function scopeArea(Builder $q, ?string $area): Builder
    {
        return $area ? $q->where('area', $area) : $q;
    }

    /** 受取日 From（Y-m-d） */
    public function scopeReceiveFrom(Builder $q, ?string $from): Builder
    {
        return $from ? $q->whereDate('receive_date', '>=', $from) : $q;
    }

    /** 受取日 To（Y-m-d） */
    public function scopeReceiveTo(Builder $q, ?string $to): Builder
    {
        return $to ? $q->whereDate('receive_date', '<=', $to) : $q;
    }

    /**
     * キーワード検索（氏名/電話/注文コード/住所/備考など）
     * - 必要に応じて列を足してOK
     */
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

            // 完全一致でID検索したい場合
            if (ctype_digit($keyword)) {
                $w->orWhere('id', (int) $keyword);
            }
        });
    }
}
