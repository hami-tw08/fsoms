<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class TimeSlot extends Model
{
    protected $fillable = [
        'date','start_time','end_time','type','capacity','reserved_count'
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function scopeActiveDays($q) {
        // 営業は水木金土日（Mon/Tue は休）
        return $q->whereRaw("WEEKDAY(date) IN (2,3,4,5,6)"); // MySQL: 0=Mon -> Sun=6
    }

    public function getLabelAttribute(): string {
        $d = $this->date instanceof Carbon ? $this->date : Carbon::parse($this->date);
        return sprintf('%s %s-%s (%s)',
            $d->format('Y-m-d'),
            substr($this->start_time,0,5),
            substr($this->end_time,0,5),
            $this->type
        );
    }
}
