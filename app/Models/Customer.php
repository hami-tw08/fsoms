<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany; // ★ 追記

class Customer extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'email',
    ];

    /**
     * ★ 追記：この顧客の予約一覧
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'customer_id');
    }
}
