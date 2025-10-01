<?php
// app/Models/Slot.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Slot extends Model
{
    protected $fillable = [
        'date','start_time','end_time','slot_type','capacity','remaining',
        'notify_threshold','notified_low_at',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'notified_low_at' => 'datetime',
    ];

    public function isLow(): bool
    {
        return $this->remaining !== null
            && $this->remaining <= $this->notify_threshold;
    }

    public function shouldNotify(): bool
    {
        return $this->isLow() && $this->notified_low_at === null;
    }
}
