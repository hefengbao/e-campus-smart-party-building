<?php

namespace App\Jobs;

use App\Models\Meeting;
use App\Models\MeetingPlan;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\MeetingReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * 三会一课填报提醒：月度计划未完成提醒（每月 1 日）+ 长期未填报提醒（每周一）。
 *
 * 提醒对象：组织关联的支部委员账号（在任委员的 user）。
 */
class MeetingReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 执行提醒。
     */
    public function handle(): void
    {
        $this->remindMonthlyUnfinished();
        $this->remindLongTimeNoMeeting();
    }

    /**
     * 月度：上月计划未完成的组织，提醒委员补报。
     */
    private function remindMonthlyUnfinished(): void
    {
        $lastMonth = now()->subMonth()->startOfMonth();
        $year = (int) $lastMonth->format('Y');
        $month = (int) $lastMonth->format('n');

        MeetingPlan::query()
            ->where('year', $year)
            ->where('month', $month)
            ->get()
            ->groupBy('org_id')
            ->each(function ($plans, $orgId): void {
                $planned = (int) $plans->sum('planned_times');
                $actual = Meeting::query()
                    ->where('org_id', $orgId)
                    ->whereNotNull('actual_at')
                    ->whereYear('actual_at', $year)
                    ->whereMonth('actual_at', $month)
                    ->count();

                if ($actual >= $planned) {
                    return;
                }

                $org = Organization::find($orgId);

                $this->notifyCommitteeUsers((int) $orgId, [
                    'category' => '组织生活',
                    'title' => ($org?->short_name ?? '组织')."上月组织生活未完成（{$actual}/{$planned}）",
                    'content' => "上月计划 {$planned} 次、实际开展 {$actual} 次，请及时补录会议记录。",
                    'biz_type' => 'meeting_plan',
                    'biz_id' => (int) $orgId,
                ]);
            });
    }

    /**
     * 长期：支部超过 90 天没有任何已提交会议，提醒委员。
     */
    private function remindLongTimeNoMeeting(): void
    {
        $cutoff = now()->subDays(90);

        Organization::query()
            ->active()
            ->where('level', 3)
            ->get(['id', 'short_name'])
            ->each(function (Organization $org) use ($cutoff): void {
                $lastMeeting = Meeting::query()
                    ->where('org_id', $org->id)
                    ->whereNotNull('actual_at')
                    ->max('actual_at');

                if ($lastMeeting !== null && $lastMeeting >= $cutoff) {
                    return;
                }

                $this->notifyCommitteeUsers($org->id, [
                    'category' => '组织生活',
                    'title' => $org->short_name.'已超过 90 天未开展组织生活',
                    'content' => '请尽快按计划开展三会一课并填报会议记录。',
                    'biz_type' => 'organization',
                    'biz_id' => $org->id,
                ]);
            });
    }

    /**
     * 通知组织全部在任委员的关联账号。
     *
     * @param  array<string, mixed>  $payload
     */
    private function notifyCommitteeUsers(int $orgId, array $payload): void
    {
        User::query()
            ->whereIn('member_id', function ($query) use ($orgId): void {
                $query->select('member_id')
                    ->from('committee_members')
                    ->where('organization_id', $orgId)
                    ->where('status', '在任');
            })
            ->get()
            ->each(fn (User $user) => $user->notify(new MeetingReminder($payload)));
    }
}
