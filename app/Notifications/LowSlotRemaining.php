<?php
// app/Notifications/LowSlotRemaining.php

namespace App\Notifications;

use App\Models\Slot;
//use Illuminate\Bus\Queueable;
//use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowSlotRemaining extends Notification
{
    public function __construct(public Slot $slot) {}

    public function via($notifiable): array
    {
        return []; // Slackなど増やしたければ追加
    }

    public function toMail($notifiable): MailMessage
    {
        $s = $this->slot;
        $title = "[要確認] 枠残数が閾値以下 ({$s->slot_type})";
        return (new MailMessage)
            ->subject($title)
            ->line("日付: {$s->date->toDateString()}")
            ->line("時間: {$s->start_time} - {$s->end_time}")
            ->line("種別: {$s->slot_type}")
            ->line("残数: {$s->remaining} / 閾値: {$s->notify_threshold}")
            ->action('管理画面で調整', url('/admin/slots?date='.$s->date->toDateString()));
    }
}
