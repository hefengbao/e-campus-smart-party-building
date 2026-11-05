<?php

namespace App\Filament\Member\Widgets;

use App\Models\DevelopmentCase;
use App\Models\FeeRecord;
use App\Models\HourStandard;
use App\Models\Trainee;
use App\Models\TrainingHour;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

/**
 * 个人工作台综合概览：
 * 兼容正式/预备党员、入党积极分子以及非党员干部等全员个人业务。
 */
class MemberOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int|array|null $columns = 4;

    protected function getStats(): array
    {
        $user = Auth::user();

        if ($user === null) {
            return [];
        }

        $now = now();
        $year = (int) $now->format('Y');
        $member = $user->member;

        // 1. 已建党员档案用户（正式党员、预备党员、部分已建档积极分子）
        if ($member !== null) {
            $joinDate = $member->join_party_date;

            // 政治生日 & 党龄
            if ($joinDate !== null) {
                $partyYears = $now->year - $joinDate->year;
                $thisAnniversary = $joinDate->copy()->year($now->year);
                $daysToBirthday = (int) $now->startOfDay()->diffInDays($thisAnniversary, false);

                if ($daysToBirthday === 0) {
                    $birthdayText = "今天政治生日（第 {$partyYears} 年）";
                } elseif ($daysToBirthday > 0) {
                    $birthdayText = "距第 {$partyYears} 年还剩 {$daysToBirthday} 天";
                } else {
                    $nextAnniversary = $joinDate->copy()->year($now->year + 1);
                    $daysToNext = (int) $now->startOfDay()->diffInDays($nextAnniversary, false);
                    $birthdayText = "距下次政治生日 {$daysToNext} 天";
                }
            } else {
                $partyYears = 0;
                $birthdayText = '未录入入党日期';
            }

            // 年度学时达标
            $targetKey = $member->relation_in_school === '教职工' ? '教职工党员' : '学生党员';
            $requiredHours = HourStandard::requiredHoursFor($year, $targetKey) ?: HourStandard::requiredHoursFor($year);

            $effectiveHours = (float) TrainingHour::query()
                ->where('member_id', $member->id)
                ->where('year', $year)
                ->where('status', '已生效')
                ->sum('hours');

            $isHourCompliant = $requiredHours <= 0 || $effectiveHours >= $requiredHours;

            // 当月党费状态
            $currentPeriod = $now->format('Y-m');
            $feeRecord = FeeRecord::query()
                ->where('member_id', $member->id)
                ->where('period', $currentPeriod)
                ->first();

            $feeStatus = $feeRecord?->pay_status ?? '未交纳';

            return [
                Stat::make('所在支部', $member->organization?->short_name ?? '—')
                    ->description($member->identity ?? '党员')
                    ->color('primary'),

                Stat::make('政治生日', $partyYears > 0 ? "党龄 {$partyYears} 年" : '党员')
                    ->description($birthdayText)
                    ->color('warning'),

                Stat::make('年度学时', "{$effectiveHours} / {$requiredHours} 学时")
                    ->description($isHourCompliant ? '年度考核已达标' : '还需 '.($requiredHours - $effectiveHours).' 学时')
                    ->color($isHourCompliant ? 'success' : 'danger'),

                Stat::make('当月党费', $feeStatus)
                    ->description("所属月份 {$currentPeriod}")
                    ->color($feeStatus === '已交纳' ? 'success' : 'warning'),
            ];
        }

        // 2. 非党员干部（存在 Trainee 学员档案）
        $trainee = Trainee::query()->where('staff_no', $user->id)->first();

        if ($trainee !== null) {
            $requiredHours = HourStandard::requiredHoursFor($year, '干部') ?: HourStandard::requiredHoursFor($year);
            $effectiveHours = (float) TrainingHour::query()
                ->where('trainee_id', $trainee->id)
                ->where('year', $year)
                ->where('status', '已生效')
                ->sum('hours');

            $isHourCompliant = $requiredHours <= 0 || $effectiveHours >= $requiredHours;

            return [
                Stat::make('干部培训档案', $trainee->cadre_type ?? '干部培训学员')
                    ->description($trainee->department ?? $trainee->org_name ?? '学校教职工')
                    ->color('primary'),

                Stat::make('政治面貌', $trainee->political_status ?? '党外干部')
                    ->description('状态：'.($trainee->status ?? '在训'))
                    ->color('info'),

                Stat::make('干部年度学时', "{$effectiveHours} / {$requiredHours} 学时")
                    ->description($isHourCompliant ? '年度培训达标' : '还需 '.($requiredHours - $effectiveHours).' 学时')
                    ->color($isHourCompliant ? 'success' : 'warning'),

                Stat::make('个人业务中心', '正常运行')
                    ->description('学时/申请/工作坊交流')
                    ->color('success'),
            ];
        }

        // 3. 入党申请人/积极分子（未建党员档案但已有入党发展案）
        $devCase = DevelopmentCase::query()->where('member_id', $user->id)->first();

        if ($devCase !== null) {
            $step = $devCase->currentStep();

            return [
                Stat::make('入党发展进度', '第 '.$devCase->stage_no.' 阶段')
                    ->description($devCase->status ?? '进行中')
                    ->color('primary'),

                Stat::make('当前步骤', '第 '.$devCase->step_no.' 步')
                    ->description($step?->step_name ?? '—')
                    ->color($devCase->isOverdue() ? 'danger' : 'info'),

                Stat::make('所在党组织', $devCase->organization?->short_name ?? '—')
                    ->description('一人一案跟踪中')
                    ->color('warning'),

                Stat::make('个人工作台', '就绪')
                    ->description('材料提交/进度跟踪')
                    ->color('success'),
            ];
        }

        // 4. 普通注册个人用户
        return [
            Stat::make('个人工作台', $user->name)
                ->description('工号：'.$user->id)
                ->color('primary'),

            Stat::make('党务工作坊', '在线交流')
                ->description('查阅活动与政策问答')
                ->color('info'),

            Stat::make('个人申请', '服务大厅')
                ->description('手机号/转接/出国境')
                ->color('success'),

            Stat::make('消息提醒', '正常')
                ->description('站内业务通知')
                ->color('gray'),
        ];
    }
}
