<?php

namespace App\Filament\College\Widgets;

use App\Models\AbroadApplication;
use App\Models\Alert;
use App\Models\Meeting;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Transfer;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

/**
 * 二级党委驾驶舱概览：辖属组织、党员规模、院级待审核待办、辖属预警。
 * 数据严格按当前二级党委委员 scopedOrgIds() 收敛。
 */
class CollegeOverviewWidget extends StatsOverviewWidget
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

        // 辖属党支部数
        $branchesCount = Organization::query()
            ->active()
            ->where('level', 3)
            ->when($orgIds !== [], fn ($q) => $q->whereIn('id', $orgIds))
            ->count();

        // 辖属党员总数
        $membersQuery = Member::query()
            ->where('status', '正常')
            ->visibleTo($orgIds);

        $totalMembers = (clone $membersQuery)->count();
        $formalCount = (clone $membersQuery)->where('identity', '正式党员')->count();
        $probationaryCount = (clone $membersQuery)->where('identity', '预备党员')->count();

        // 院级待审核事项合计：
        // 1. 三会一课「已提交」
        $pendingMeetings = Meeting::query()
            ->where('status', '已提交')
            ->when($orgIds !== [], fn ($q) => $q->whereIn('org_id', $orgIds))
            ->count();

        // 2. 组织关系转接「待院级审核」
        $pendingTransfers = Transfer::query()
            ->where('status', '待院级审核')
            ->when($orgIds !== [], fn ($q) => $q->whereIn('from_org_id', $orgIds))
            ->count();

        // 3. 因私出国（境）「待院级审核」
        $pendingAbroad = AbroadApplication::query()
            ->where('status', '待院级审核')
            ->when($orgIds !== [], fn ($q) => $q->visibleTo($orgIds))
            ->count();

        $totalPendingReviews = $pendingMeetings + $pendingTransfers + $pendingAbroad;

        // 辖属未处置预警
        $pendingAlerts = Alert::query()
            ->where('status', '未处置')
            ->when($orgIds !== [], fn ($q) => $q->whereIn('org_id', $orgIds));

        $alertCount = (clone $pendingAlerts)->count();
        $seriousAlertCount = (clone $pendingAlerts)->where('level', '严重')->count();

        return [
            Stat::make('辖属支部', "{$branchesCount} 个")
                ->description('所辖党支部总数')
                ->color('primary'),

            Stat::make('所辖党员', "{$totalMembers} 人")
                ->description("正式 {$formalCount} · 预备 {$probationaryCount}")
                ->color('info'),

            Stat::make('待审事项', "{$totalPendingReviews} 件")
                ->description("三会一课 {$pendingMeetings} · 转接 {$pendingTransfers} · 出国 {$pendingAbroad}")
                ->color($totalPendingReviews > 0 ? 'warning' : 'success'),

            Stat::make('监督预警', "{$alertCount} 条")
                ->description($seriousAlertCount > 0 ? "严重级别 {$seriousAlertCount} 条" : '运行正常')
                ->color($seriousAlertCount > 0 ? 'danger' : ($alertCount > 0 ? 'warning' : 'success')),
        ];
    }
}
