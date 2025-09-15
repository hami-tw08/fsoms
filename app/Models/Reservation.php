<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reservation extends Model
{
    protected $fillable = [
        'order_code',
        'method',       // store|delivery
        'receive_date', // Y-m-d
        'receive_time', // H:i
        'total',
        'status',       // pending|confirmed
        // 注文者
        'orderer_name',
        'orderer_phone',
        // 配送時のみ
        'recipient_name',
        'recipient_company',
        'recipient_store',
        'area',
        'address',
    ];

    protected $casts = [
        'receive_date' => 'date',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(ReservationItem::class);
    }
}
