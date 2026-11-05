<?php

namespace App\Notifications;

use App\Models\WorkshopActivity;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * 工作坊活动审核结果站内通知（database channel）。
 */
class WorkshopActivityReviewed extends Notification
{
    use Queueable;

    public function __construct(
        public WorkshopActivity $activity,
        public bool $approved,
        public ?string $reason = null,
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
            'category' => '工作坊',
            'title' => $this->approved
                ? '您发布的活动「'.$this->activity->title.'」已审核通过并发布'
                : '您发布的活动「'.$this->activity->title.'」被驳回',
            'content' => $this->approved
                ? '请及时关注报名情况，并在活动结束后 7 日内完成总结归档。'
                : ('驳回原因：'.($this->reason ?? '未填写')),
            'biz_type' => 'workshop_activity',
            'biz_id' => $this->activity->id,
        ];
    }
}
