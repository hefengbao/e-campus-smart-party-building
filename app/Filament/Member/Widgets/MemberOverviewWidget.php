<?php

namespace App\Filament\Member\Widgets;

use App\Models\FeeRecord;
use App\Models\HourStandard;
use App\Models\TrainingHour;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

/**
 * 普通党员个人党建档案概览：政治生日/党龄、所在支部、年度学时达标、当月党费状态。
 */
class MemberOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int|array|null $columns = 4;

    protected function getStats(): array
    {
        $member = Auth::user()?->member;

        if ($member === null) {
            return [
                Stat::make('党员档案', '未关联')
                    ->description('当前账号未关联党员档案')
                    ->color('gray'),
            ];
        }

        $now = now();
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
        $year = (int) $now->format('Y');
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
}
