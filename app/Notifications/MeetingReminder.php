<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * 三会一课填报提醒（database channel）。
 *
 * @phpstan-type Payload array{category: string, title: string, content: string, biz_type: string, biz_id: int}
 */
class MeetingReminder extends Notification
{
    use Queueable;

    /**
     * @param  Payload  $payload
     */
    public function __construct(
        public array $payload,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->payload;
    }
}
