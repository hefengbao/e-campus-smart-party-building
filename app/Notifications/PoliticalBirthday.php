<?php

namespace App\Notifications;

use App\Models\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * 政治生日站内祝福（database channel）。
 */
class PoliticalBirthday extends Notification
{
    use Queueable;

    public function __construct(
        public Member $member,
        public int $years,
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
        return [
            'category' => '政治生日',
            'title' => "今天是您的第 {$this->years} 个政治生日",
            'content' => "今天是您的第 {$this->years} 个政治生日，感谢您为党的事业作出的贡献！",
            'biz_type' => 'birthday_push',
            'biz_id' => $this->member->id,
        ];
    }
}
