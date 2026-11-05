<?php

namespace App\Jobs;

use App\Models\BirthdayPush;
use App\Models\Member;
use App\Models\User;
use App\Notifications\PoliticalBirthday;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * 政治生日推送：为未来 7 天内入党周年的党员生成台账（幂等）并发送站内通知。
 *
 * 台账幂等键：(member_id, birthday_date, channel)。
 * 当天到期的门户渠道记录直接标记已推送（门户由外部系统拉取）。
 */
class PushBirthdayJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** 提前生成窗口（天） */
    public const int ADVANCE_DAYS = 7;

    /**
     * 执行推送。
     */
    public function handle(): void
    {
        $today = now()->startOfDay();

        Member::query()
            ->where('status', '正常')
            ->whereNotNull('join_party_date')
            ->with('organization')
            ->get()
            ->each(function (Member $member) use ($today): void {
                $joinDate = $member->join_party_date;
                $anniversary = $joinDate->copy()->year((int) $today->format('Y'));
                $daysUntil = (int) $today->diffInDays($anniversary, false);

                // 未来 7 天内（含今天）的入党周年。
                if ($daysUntil < 0 || $daysUntil > self::ADVANCE_DAYS) {
                    return;
                }

                $years = $today->year - $joinDate->year;
                $content = "今天是您的第 {$years} 个政治生日，感谢您为党的事业作出的贡献！";

                foreach (BirthdayPush::CHANNELS as $channel) {
                    $push = BirthdayPush::query()
                        ->where('member_id', $member->id)
                        ->whereDate('birthday_date', $anniversary)
                        ->where('channel', $channel)
                        ->first();

                    if ($push === null) {
                        $push = BirthdayPush::query()->create([
                            'member_id' => $member->id,
                            'birthday_date' => $anniversary->toDateString(),
                            'channel' => $channel,
                            'years' => $years,
                            'content' => $content,
                        ]);
                    } else {
                        $push->fill([
                            'years' => $years,
                            'content' => $content,
                        ])->save();
                    }

                    // 当天到期：门户渠道由门户系统拉取展示，直接标记已推送。
                    if ($daysUntil === 0 && $channel === '门户' && in_array($push->status, ['待推送', null], true)) {
                        $push->forceFill([
                            'status' => '已推送',
                            'pushed_at' => now(),
                        ])->save();
                    }
                }

                // 当天到期：发送站内通知（有账号的党员）。
                if ($daysUntil === 0) {
                    $userId = $member->user_id;

                    if ($userId !== null && ($user = User::find($userId)) !== null) {
                        $user->notify(new PoliticalBirthday($member, $years));
                    }
                }
            });
    }
}
