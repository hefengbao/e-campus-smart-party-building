<?php

namespace App\Filament\Member\Widgets;

use App\Models\TrainingHour;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class TrainingProfileWidget extends StatsOverviewWidget
{
    protected int|array|null $columns = 4;

    protected function getStats(): array
    {
        $memberId = Auth::user()?->member?->id;

        if ($memberId === null) {
            return [
                Stat::make('培训学时', '—')
                    ->description('当前账号未关联党员档案')
                    ->color('gray'),
            ];
        }

        $effective = TrainingHour::query()
            ->where('member_id', $memberId)
            ->where('status', '已生效');

        $total = (float) (clone $effective)->sum('hours');
        $online = (float) (clone $effective)->where('training_category', '网络自学')->sum('hours');
        $offline = (float) (clone $effective)->where('training_category', '集中培训')->sum('hours');

        $pendingHours = (float) TrainingHour::query()
            ->where('member_id', $memberId)
            ->where('status', '待审核')
            ->sum('hours');

        return [
            Stat::make('总学时', $total)
                ->description('仅统计已生效学时')
                ->color('success'),
            Stat::make('网络自学', $online)
                ->description('已生效学时合计')
                ->color('info'),
            Stat::make('集中培训', $offline)
                ->description('已生效学时合计')
                ->color('warning'),
            Stat::make('待审核学时', $pendingHours)
                ->description($pendingHours > 0 ? '有待审核学时，审核通过后计入' : '暂无待审核学时')
                ->color($pendingHours > 0 ? 'danger' : 'gray'),
        ];
    }
}
