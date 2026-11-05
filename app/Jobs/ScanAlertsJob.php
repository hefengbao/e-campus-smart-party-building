<?php

namespace App\Jobs;

use App\Models\Alert;
use App\Models\Config;
use App\Models\DevelopmentCase;
use App\Models\DevelopmentStep;
use App\Models\DevelopmentStepDefinition;
use App\Models\HourStandard;
use App\Models\Meeting;
use App\Models\MeetingPlan;
use App\Models\Member;
use App\Models\Organization;
use App\Models\SupervisionTask;
use App\Models\TrainingHour;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * 监督预警扫描：9 类规则，dedup_key 幂等。
 *
 * 幂等语义：同 dedup_key 已存在未处置预警 → 刷新级别与 last_scanned_at；
 * 已处置/已忽略 → 不复活；不存在 → 新建。
 */
class ScanAlertsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 执行扫描。
     */
    public function handle(): void
    {
        $this->scanTermExpiry();
        $this->scanMeetingReminder();
        $this->scanDevelopmentOverdue();
        $this->scanDevelopmentMaterialsMissing();
        $this->scanThoughtReportOverdue();
        $this->scanJoinConditionMismatch();
        $this->scanProbationExpiry();
        $this->scanTrainingHours();
        $this->scanSupervisionTasks();
    }

    /**
     * 规则 1：换届到期预警。
     *
     * 应换届日（term_end_date）距今 ≤ term_warning_days（默认 180）：
     * 逾期 = 严重；剩余 ≤ 60 天 = 警告；否则提示。
     */
    private function scanTermExpiry(): void
    {
        $warningDays = (int) Config::value('term_warning_days', '180');

        Organization::query()
            ->active()
            ->whereNotNull('term_end_date')
            ->get(['id', 'short_name', 'full_name', 'term_end_date'])
            ->each(function (Organization $org) use ($warningDays): void {
                $end = $org->term_end_date;
                $daysLeft = (int) now()->startOfDay()->diffInDays($end, false);

                if ($daysLeft > $warningDays) {
                    return;
                }

                $level = $daysLeft < 0 ? '严重' : ($daysLeft <= 60 ? '警告' : '提示');
                $title = $daysLeft < 0
                    ? $org->short_name.'已逾期未换届'
                    : $org->short_name.'将于 '.abs((int) $daysLeft).' 天后任期届满';

                $this->upsertAlert(
                    rule: '换届到期预警',
                    level: $level,
                    title: $title,
                    content: '任期截止 '.($end?->format('Y-m-d') ?? '—').'，请及时启动换届程序。',
                    orgId: $org->id,
                    bizType: 'organization',
                    bizId: $org->id,
                    dedupKey: "term:{$org->id}:{$end->format('Ymd')}",
                );
            });
    }

    /**
     * 规则 2：组织生活提醒。
     *
     * 本月已过 meeting_reminder_day（默认 25 日）仍未完成当月计划会议数。
     */
    private function scanMeetingReminder(): void
    {
        $reminderDay = (int) Config::value('meeting_reminder_day', '25');
        $today = now()->startOfDay();

        if ((int) $today->format('j') <= $reminderDay) {
            return;
        }

        $year = (int) $today->format('Y');
        $month = (int) $today->format('n');

        MeetingPlan::query()
            ->where('year', $year)
            ->where('month', $month)
            ->get()
            ->groupBy('org_id')
            ->each(function ($plans, $orgId) use ($year, $month): void {
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

                $this->upsertAlert(
                    rule: '组织生活提醒',
                    level: '提示',
                    title: $org?->short_name."本月组织生活未完成（{$actual}/{$planned}）",
                    content: "本月已过提醒日，计划 {$planned} 次、实际开展 {$actual} 次，请尽快补齐。",
                    orgId: (int) $orgId,
                    bizType: 'meeting_plan',
                    bizId: (int) $orgId,
                    dedupKey: "meeting:{$orgId}:{$year}".str_pad((string) $month, 2, '0', STR_PAD_LEFT),
                );
            });
    }

    /**
     * 规则 3：发展流程超期。
     *
     * 进行中案件当前步骤超过 dev_step_overdue_days（默认 30）天：
     * 超过 2 倍阈值 = 严重，否则警告。
     */
    private function scanDevelopmentOverdue(): void
    {
        $overdueDays = (int) Config::value('dev_step_overdue_days', '30');

        DevelopmentCase::query()
            ->where('status', '进行中')
            ->with(['member', 'organization'])
            ->get()
            ->each(function (DevelopmentCase $case) use ($overdueDays): void {
                $step = DevelopmentStep::query()
                    ->where('case_id', $case->id)
                    ->where('step_no', $case->step_no)
                    ->first();

                if ($step === null || $step->status === '已完成') {
                    return;
                }

                $startedAt = $step->planned_date ?? $case->started_at?->startOfDay();

                if ($startedAt === null) {
                    return;
                }

                $elapsed = $startedAt->diffInDays(now()->startOfDay(), false);

                if ($elapsed <= $overdueDays) {
                    return;
                }

                $level = $elapsed > $overdueDays * 2 ? '严重' : '警告';

                $this->upsertAlert(
                    rule: '发展流程超期',
                    level: $level,
                    title: ($case->member?->name ?? '党员').'发展流程当前步骤已停留 '.(int) $elapsed.' 天',
                    content: '当前步骤「'.$step->step_name.'」超过 '.$overdueDays.' 天未完成，请尽快推进。',
                    orgId: $case->org_id,
                    memberId: $case->member_id,
                    bizType: 'development_case',
                    bizId: $case->id,
                    dedupKey: "dev_overdue:{$case->id}:{$case->step_no}",
                );
            });
    }

    /**
     * 规则 4：发展材料缺失。
     *
     * 进行中案件当前步骤 required_materials 非空但 materials 为空 = 警告。
     */
    private function scanDevelopmentMaterialsMissing(): void
    {
        DevelopmentCase::query()
            ->where('status', '进行中')
            ->with('member')
            ->get()
            ->each(function (DevelopmentCase $case): void {
                $step = DevelopmentStep::query()
                    ->where('case_id', $case->id)
                    ->where('step_no', $case->step_no)
                    ->first();

                if ($step === null || $step->status === '已完成') {
                    return;
                }

                $definition = DevelopmentStepDefinition::query()
                    ->where('step_no', $case->step_no)
                    ->first();

                $required = $definition?->required_materials ?? [];

                if ($required === [] || filled($step->materials)) {
                    return;
                }

                $this->upsertAlert(
                    rule: '发展材料缺失',
                    level: '警告',
                    title: ($case->member?->name ?? '党员').'发展流程第 '.$case->step_no.' 步材料缺失',
                    content: '当前步骤「'.$step->step_name.'」要求材料：'.implode('、', $required).'。',
                    orgId: $case->org_id,
                    memberId: $case->member_id,
                    bizType: 'development_case',
                    bizId: $case->id,
                    dedupKey: "dev_material:{$case->id}:{$case->step_no}",
                );
            });
    }

    /**
     * 规则 5：思想汇报逾期。
     *
     * 第 6 步（培养教育考察）停留期间，最近一次思想汇报（材料含"思想汇报"）
     * 距今超过 90 天 = 警告。
     */
    private function scanThoughtReportOverdue(): void
    {
        DevelopmentCase::query()
            ->where('status', '进行中')
            ->where('step_no', 6)
            ->with('member')
            ->get()
            ->each(function (DevelopmentCase $case): void {
                $step = DevelopmentStep::query()
                    ->where('case_id', $case->id)
                    ->where('step_no', 6)
                    ->first();

                if ($step === null) {
                    return;
                }

                $lastReportAt = $this->lastThoughtReportDate($case->id);

                if ($lastReportAt !== null && $lastReportAt->diffInDays(now()->startOfDay(), false) <= 90) {
                    return;
                }

                $this->upsertAlert(
                    rule: '思想汇报逾期',
                    level: '警告',
                    title: ($case->member?->name ?? '党员').'思想汇报逾期',
                    content: $lastReportAt === null
                        ? '培养教育考察期间尚未提交任何思想汇报。'
                        : '最近一次思想汇报提交于 '.$lastReportAt->format('Y-m-d').'，已超过 90 天。',
                    orgId: $case->org_id,
                    memberId: $case->member_id,
                    bizType: 'development_case',
                    bizId: $case->id,
                    dedupKey: "thought:{$case->id}",
                );
            });
    }

    /**
     * 规则 6：发展条件不符。
     *
     * 入党时间 − 出生日期 < 18 年 = 严重；预备期 < 365 天 = 警告；
     * special_note 已填写则豁免。
     */
    private function scanJoinConditionMismatch(): void
    {
        Member::query()
            ->whereIn('identity', ['正式党员', '预备党员'])
            ->with('organization')
            ->get()
            ->each(function (Member $member): void {
                if (filled($member->special_note)) {
                    return;
                }

                $joinDate = $member->join_party_date;

                if ($joinDate !== null && $member->birth_date !== null
                    && $member->birth_date->diffInYears($joinDate) < 18) {
                    $this->upsertAlert(
                        rule: '发展条件不符',
                        level: '严重',
                        title: $member->name.'入党年龄不满 18 周岁',
                        content: '出生 '.($member->birth_date?->format('Y-m-d') ?? '—')
                            .'，入党 '.$joinDate->format('Y-m-d').'，间隔不足 18 年。',
                        orgId: $member->org_id,
                        memberId: $member->id,
                        bizType: 'member',
                        bizId: null,
                        dedupKey: "join_age:{$member->id}",
                    );
                }

                $fullMemberDate = $member->full_member_date;

                if ($member->identity === '正式党员' && $joinDate !== null && $fullMemberDate !== null
                    && $joinDate->diffInDays($fullMemberDate, false) < 365) {
                    $this->upsertAlert(
                        rule: '发展条件不符',
                        level: '警告',
                        title: $member->name.'预备期不足 1 年即转正',
                        content: '入党 '.$joinDate->format('Y-m-d')
                            .'，转正 '.$fullMemberDate->format('Y-m-d').'，预备期不足 365 天。',
                        orgId: $member->org_id,
                        memberId: $member->id,
                        bizType: 'member',
                        bizId: null,
                        dedupKey: "probation_short:{$member->id}",
                    );
                }
            });
    }

    /**
     * 规则 7：预备期到期。
     *
     * 预备党员入党满 1 年前后 probation_warning_days（默认 30）天内提醒；
     * 逾期未转正 = 严重。
     */
    private function scanProbationExpiry(): void
    {
        $warningDays = (int) Config::value('probation_warning_days', '30');

        Member::query()
            ->where('identity', '预备党员')
            ->whereNotNull('join_party_date')
            ->with('organization')
            ->get()
            ->each(function (Member $member) use ($warningDays): void {
                $dueDate = $member->join_party_date->copy()->addYear();
                $daysLeft = (int) now()->startOfDay()->diffInDays($dueDate, false);

                if ($daysLeft > $warningDays) {
                    return;
                }

                $level = $daysLeft < 0 ? '严重' : '提示';
                $title = $daysLeft < 0
                    ? $member->name.'预备期已逾期未转正'
                    : $member->name.'预备期将于 '.abs((int) $daysLeft).' 天后届满';

                $this->upsertAlert(
                    rule: '预备期到期',
                    level: $level,
                    title: $title,
                    content: '入党 '.$member->join_party_date->format('Y-m-d')
                        .'，应转正日期 '.$dueDate->format('Y-m-d').'，请及时办理转正手续。',
                    orgId: $member->org_id,
                    memberId: $member->id,
                    bizType: 'member',
                    bizId: null,
                    dedupKey: "probation:{$member->id}:{$dueDate->format('Ymd')}",
                );
            });
    }

    /**
     * 规则 8：学时未达标。
     *
     * 年度已生效学时 < HourStandard：不足 50% = 警告，否则提示。
     */
    private function scanTrainingHours(): void
    {
        $year = (int) now()->format('Y');
        $required = HourStandard::requiredHoursFor($year);

        if ($required <= 0) {
            return;
        }

        Member::query()
            ->where('status', '正常')
            ->with('organization')
            ->get()
            ->each(function (Member $member) use ($year, $required): void {
                $targetKey = $member->relation_in_school === '教职工' ? '教职工党员' : '学生党员';
                $memberRequired = HourStandard::requiredHoursFor($year, $targetKey) ?: $required;

                $hours = (float) TrainingHour::query()
                    ->where('member_id', $member->id)
                    ->where('year', $year)
                    ->where('status', '已生效')
                    ->sum('hours');

                if ($hours >= $memberRequired) {
                    return;
                }

                $level = $hours < $memberRequired / 2 ? '警告' : '提示';

                $this->upsertAlert(
                    rule: '学时未达标',
                    level: $level,
                    title: $member->name.$year.' 年学时未达标',
                    content: "已生效学时 {$hours}，标准 {$memberRequired}，请尽快完成培训任务。",
                    orgId: $member->org_id,
                    memberId: $member->id,
                    bizType: 'member',
                    bizId: null,
                    dedupKey: "hours:{$member->id}:{$year}",
                );
            });
    }

    /**
     * 规则 9：党建任务督办。
     *
     * 进行中任务 due_date ≤ 7 天提醒；逾期 = 严重。
     */
    private function scanSupervisionTasks(): void
    {
        SupervisionTask::query()
            ->where('status', '进行中')
            ->whereNotNull('due_date')
            ->with('organization')
            ->get()
            ->each(function (SupervisionTask $task): void {
                $daysLeft = (int) now()->startOfDay()->diffInDays($task->due_date, false);

                if ($daysLeft > 7) {
                    return;
                }

                $level = $daysLeft < 0 ? '严重' : '提示';
                $title = $daysLeft < 0
                    ? '任务「'.$task->title.'」已逾期'
                    : '任务「'.$task->title.'」将于 '.abs((int) $daysLeft).' 天后到期';

                $this->upsertAlert(
                    rule: '党建任务督办',
                    level: $level,
                    title: $title,
                    content: '截止日期 '.$task->due_date->format('Y-m-d').'，请尽快完成并反馈。',
                    orgId: $task->org_id,
                    bizType: 'supervision_task',
                    bizId: $task->id,
                    dedupKey: "task:{$task->id}:{$task->due_date->format('Ymd')}",
                );
            });
    }

    /**
     * 最近一次思想汇报提交日期（materials 含"思想汇报"的步骤完成时间）。
     */
    private function lastThoughtReportDate(int $caseId): ?Carbon
    {
        $steps = DevelopmentStep::query()
            ->where('case_id', $caseId)
            ->where('status', '已完成')
            ->whereNotNull('done_date')
            ->orderByDesc('done_date')
            ->get(['materials', 'done_date']);

        foreach ($steps as $step) {
            $materials = $step->materials ?? [];

            if (in_array('思想汇报', $materials, true)) {
                return $step->done_date;
            }
        }

        return null;
    }

    /**
     * 幂等写入预警：存在未处置 → 刷新；已处置/已忽略 → 跳过；否则新建。
     */
    private function upsertAlert(
        string $rule,
        string $level,
        string $title,
        string $content,
        int $orgId,
        string $bizType,
        ?int $bizId,
        string $dedupKey,
        ?string $memberId = null,
    ): void {
        $existing = Alert::query()->where('dedup_key', $dedupKey)->first();

        if ($existing !== null) {
            if ($existing->status === '未处置') {
                $existing->forceFill([
                    'level' => $level,
                    'title' => $title,
                    'content' => $content,
                    'last_scanned_at' => now(),
                ])->save();
            }

            return;
        }

        Alert::query()->create([
            'rule' => $rule,
            'level' => $level,
            'title' => $title,
            'content' => $content,
            'org_id' => $orgId,
            'member_id' => $memberId,
            'biz_type' => $bizType,
            'biz_id' => $bizId,
            'dedup_key' => $dedupKey,
            'status' => '未处置',
            'last_scanned_at' => now(),
        ]);
    }
}
