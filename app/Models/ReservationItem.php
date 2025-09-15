<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationItem extends Model
{
    protected $fillable = [
        'reservation_id',
        'product_id',
        'name',
        'price',
        'qty',
        'subtotal',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}
