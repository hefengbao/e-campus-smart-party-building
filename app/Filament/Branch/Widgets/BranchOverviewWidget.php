<?php

namespace App\Filament\Branch\Widgets;

use App\Models\Alert;
use App\Models\DevelopmentCase;
use App\Models\Meeting;
use App\Models\Member;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

/**
 * 支部驾驶舱概览：支部党员结构、三会一课进度、发展流程、待处置预警。
 * 数据严格按当前支部委员 scopedOrgIds() 收敛。
 */
class BranchOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int|array|null $columns = 4;

    protected function getStats(): array
    {
        $user = Auth::user();

        if ($user === null) {
            return [];
        }

        $orgIds = $user->scopedOrgIds();

        // 支部党员总数与结构
        $membersQuery = Member::query()
            ->where('status', '正常')
            ->visibleTo($orgIds);

        $totalMembers = (clone $membersQuery)->count();
        $formalCount = (clone $membersQuery)->where('identity', '正式党员')->count();
        $probationaryCount = (clone $membersQuery)->where('identity', '预备党员')->count();

        // 当月组织生活开展情况
        $now = now();
        $currentMonthMeetings = Meeting::query()
            ->when($orgIds !== [], fn ($q) => $q->whereIn('org_id', $orgIds))
            ->whereNotNull('actual_at')
            ->whereYear('actual_at', $now->year)
            ->whereMonth('actual_at', $now->month)
            ->count();

        $yearMeetings = Meeting::query()
            ->when($orgIds !== [], fn ($q) => $q->whereIn('org_id', $orgIds))
            ->whereNotNull('actual_at')
            ->whereYear('actual_at', $now->year)
            ->count();

        // 进行中发展党员案件
        $devQuery = DevelopmentCase::query()
            ->where('status', '进行中')
            ->when($orgIds !== [], fn ($q) => $q->whereIn('org_id', $orgIds));

        $devCount = (clone $devQuery)->count();
        $devOverdueCount = (clone $devQuery)->get()->filter(fn (DevelopmentCase $c): bool => $c->isOverdue())->count();

        // 待处置预警
        $pendingAlerts = Alert::query()
            ->where('status', '未处置')
            ->when($orgIds !== [], fn ($q) => $q->whereIn('org_id', $orgIds));

        $alertCount = (clone $pendingAlerts)->count();
        $seriousAlertCount = (clone $pendingAlerts)->where('level', '严重')->count();

        return [
            Stat::make('支部党员', "{$totalMembers} 人")
                ->description("正式 {$formalCount} 人 · 预备 {$probationaryCount} 人")
                ->color('primary'),

            Stat::make('组织生活', "本月 {$currentMonthMeetings} 次")
                ->description("本年度已开展 {$yearMeetings} 次")
                ->color($currentMonthMeetings > 0 ? 'success' : 'warning'),

            Stat::make('发展党员', "{$devCount} 人在培")
                ->description($devOverdueCount > 0 ? "{$devOverdueCount} 人步骤超期" : '进度正常')
                ->color($devOverdueCount > 0 ? 'danger' : 'info'),

            Stat::make('待处置预警', "{$alertCount} 条")
                ->description($seriousAlertCount > 0 ? "严重级别 {$seriousAlertCount} 条" : '已常态化监控')
                ->color($alertCount > 0 ? ($seriousAlertCount > 0 ? 'danger' : 'warning') : 'success'),
        ];
    }
}
