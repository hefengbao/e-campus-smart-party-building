<?php

namespace App\Filament\Org\Widgets;

use App\Models\AbroadApplication;
use App\Models\Alert;
use App\Models\Member;
use App\Models\Organization;
use App\Models\SupervisionTask;
use App\Models\Transfer;
use App\Models\WorkshopActivity;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

/**
 * 党委组织部驾驶舱概览：全校党组织与党员规模、组织部终审待办、监督预警全览、督办任务。
 * 数据严格按当前用户 scopedOrgIds()（组织部管理员为空数组，代表全校）收敛。
 */
class OrgOverviewWidget extends StatsOverviewWidget
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

        // 基层组织规模
        $collegesCount = Organization::query()
            ->active()
            ->where('level', 2)
            ->when($orgIds !== [], fn ($q) => $q->whereIn('id', $orgIds))
            ->count();

        $branchesCount = Organization::query()
            ->active()
            ->where('level', 3)
            ->when($orgIds !== [], fn ($q) => $q->whereIn('id', $orgIds))
            ->count();

        // 党员总数
        $membersQuery = Member::query()
            ->where('status', '正常')
            ->visibleTo($orgIds);

        $totalMembers = (clone $membersQuery)->count();
        $formalCount = (clone $membersQuery)->where('identity', '正式党员')->count();
        $probationaryCount = (clone $membersQuery)->where('identity', '预备党员')->count();

        // 组织部待终审业务：
        // 1. 转接「待组织部审核」
        $pendingTransfers = Transfer::query()
            ->where('status', '待组织部审核')
            ->when($orgIds !== [], fn ($q) => $q->whereIn('from_org_id', $orgIds))
            ->count();

        // 2. 出国（境）「待组织部审核」
        $pendingAbroad = AbroadApplication::query()
            ->where('status', '待组织部审核')
            ->when($orgIds !== [], fn ($q) => $q->visibleTo($orgIds))
            ->count();

        // 3. 工作坊公开活动「待审核」
        $pendingWorkshops = WorkshopActivity::query()
            ->where('status', '待审核')
            ->when($orgIds !== [], fn ($q) => $q->whereIn('org_id', $orgIds))
            ->count();

        $totalPending = $pendingTransfers + $pendingAbroad + $pendingWorkshops;

        // 监督预警全览
        $pendingAlerts = Alert::query()
            ->where('status', '未处置')
            ->when($orgIds !== [], fn ($q) => $q->whereIn('org_id', $orgIds));

        $alertCount = (clone $pendingAlerts)->count();
        $seriousAlertCount = (clone $pendingAlerts)->where('level', '严重')->count();

        // 党建督办任务
        $tasksQuery = SupervisionTask::query()
            ->where('status', '进行中')
            ->when($orgIds !== [], fn ($q) => $q->whereIn('org_id', $orgIds));

        $taskCount = (clone $tasksQuery)->count();
        $overdueTaskCount = (clone $tasksQuery)->get()->filter(fn (SupervisionTask $t): bool => $t->isOverdue())->count();

        return [
            Stat::make('基层党组织', "{$collegesCount} 院 · {$branchesCount} 支")
                ->description('二级党委与党支部')
                ->color('primary'),

            Stat::make('全校党员', "{$totalMembers} 人")
                ->description("正式 {$formalCount} · 预备 {$probationaryCount}")
                ->color('info'),

            Stat::make('组织部待审', "{$totalPending} 件")
                ->description("转接 {$pendingTransfers} · 出国 {$pendingAbroad} · 工作坊 {$pendingWorkshops}")
                ->color($totalPending > 0 ? 'warning' : 'success'),

            Stat::make('督办与预警', "{$alertCount} 条预警")
                ->description($seriousAlertCount > 0
                    ? "严重预警 {$seriousAlertCount} 条 · 督办进行中 {$taskCount} (逾期 {$overdueTaskCount})"
                    : "督办进行中 {$taskCount} 个 · 逾期 {$overdueTaskCount} 个")
                ->color($seriousAlertCount > 0 || $overdueTaskCount > 0 ? 'danger' : 'success'),
        ];
    }
}
