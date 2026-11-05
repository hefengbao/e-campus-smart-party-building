<?php

namespace App\Filament\Member\Widgets;

use App\Models\Trainee;
use App\Models\TrainingHour;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TrainingProfileWidget extends StatsOverviewWidget
{
    protected int|array|null $columns = 4;

    protected function getStats(): array
    {
        $user = Auth::user();

        if ($user === null) {
            return [];
        }

        $memberId = $user->member?->id ?? $user->member_id;
        $traineeId = Trainee::query()->where('staff_no', $user->id)->value('id');

        if ($memberId === null && $traineeId === null) {
            return [
                Stat::make('培训学时', '0')
                    ->description('暂无已关联的党员或干部学员档案')
                    ->color('gray'),
            ];
        }

        $baseQuery = TrainingHour::query()->where(function (Builder $query) use ($memberId, $traineeId): void {
            if ($memberId !== null) {
                $query->where('member_id', $memberId);
            }

            if ($traineeId !== null) {
                $query->orWhere('trainee_id', $traineeId);
            }
        });

        $effective = (clone $baseQuery)->where('status', '已生效');

        $total = (float) (clone $effective)->sum('hours');
        $online = (float) (clone $effective)->where('training_category', '网络自学')->sum('hours');
        $offline = (float) (clone $effective)->where('training_category', '集中培训')->sum('hours');

        $pendingHours = (float) (clone $baseQuery)
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
