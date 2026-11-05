<?php

namespace App\Filament\College\Resources\WorkshopActivities\Tables;

use App\Models\WorkshopActivity;
use App\Models\WorkshopEnrollment;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class WorkshopActivitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('活动标题')
                    ->searchable()
                    ->limit(30),
                TextColumn::make('category')
                    ->label('分类')
                    ->badge()
                    ->color('info'),
                TextColumn::make('start_at')
                    ->label('开始时间')
                    ->dateTime('m-d H:i')
                    ->sortable(),
                TextColumn::make('enrollments_count')
                    ->counts('enrollments')
                    ->label('报名数'),
                TextColumn::make('status')
                    ->label('流程状态')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '草稿' => 'gray',
                        '待审核' => 'warning',
                        '已驳回' => 'danger',
                        '已发布' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('effective_status')
                    ->label('展示状态')
                    ->state(fn (WorkshopActivity $record): string => $record->effectiveStatus())
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '报名中' => 'info',
                        '报名截止' => 'warning',
                        '进行中' => 'success',
                        '已结束' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('reject_reason')
                    ->label('驳回原因')
                    ->limit(20)
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('流程状态')
                    ->options(WorkshopActivity::STATUSES),
            ])
            ->recordActions([
                // 提交审核：公开活动送组织部审核；不公开活动保存即发布。
                Action::make('submit')
                    ->label('提交')
                    ->icon('heroicon-m-paper-airplane')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription(fn (WorkshopActivity $record): string => $record->is_public
                        ? '公开活动将提交党委组织部审核，审核通过后发布。'
                        : '不公开活动保存后直接发布。')
                    ->visible(fn (WorkshopActivity $record): bool => $record->isSubmittable())
                    ->action(function (WorkshopActivity $record): void {
                        if ($record->is_public) {
                            $record->forceFill(['status' => '待审核'])->save();

                            return;
                        }

                        $record->forceFill([
                            'status' => '已发布',
                            'published_at' => now(),
                        ])->save();
                    }),
                // 总结归档：活动结束后 7 日内可归档。
                Action::make('archive')
                    ->label('总结归档')
                    ->icon('heroicon-m-archive-box')
                    ->color('success')
                    ->schema([
                        Textarea::make('summary')
                            ->label('活动总结')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (WorkshopActivity $record): bool => $record->status === '已发布'
                        && $record->effectiveStatus() === '已结束'
                        && $record->archived_at === null)
                    ->action(function (array $data, WorkshopActivity $record): void {
                        if (! $record->isWithinArchiveWindow()) {
                            Notification::make()
                                ->danger()
                                ->title('活动结束已超过 7 日，如需补录总结请联系党委组织部。')
                                ->send();

                            return;
                        }

                        $record->forceFill([
                            'summary' => $data['summary'],
                            'archived_at' => now(),
                        ])->save();
                    }),
                // 报名审核：通过/驳回本单位活动的报名。
                Action::make('review_enrollments')
                    ->label('报名审核')
                    ->icon('heroicon-m-clipboard-document-check')
                    ->color('info')
                    ->visible(fn (WorkshopActivity $record): bool => $record->enrollments()->where('status', '待审核')->exists())
                    ->form([
                        Select::make('enrollment_id')
                            ->label('待审核报名')
                            ->options(fn (WorkshopActivity $record): array => $record->enrollments()
                                ->where('status', '待审核')
                                ->with('organization')
                                ->get()
                                ->mapWithKeys(fn (WorkshopEnrollment $e): array => [
                                    $e->id => ($e->organization?->name ?? '未知组织').'（'.$e->headcount.' 人）',
                                ])
                                ->all())
                            ->required(),
                        Toggle::make('approve')
                            ->label('通过报名')
                            ->default(true),
                        Textarea::make('remark')
                            ->label('审核意见')
                            ->columnSpanFull(),
                    ])
                    ->action(function (array $data, WorkshopActivity $record): void {
                        $enrollment = WorkshopEnrollment::query()
                            ->where('activity_id', $record->id)
                            ->where('status', '待审核')
                            ->findOrFail($data['enrollment_id']);

                        $enrollment->forceFill([
                            'status' => $data['approve'] ? '已通过' : '已驳回',
                            'reviewed_by' => (string) Auth::id(),
                            'reviewed_at' => now(),
                            'remark' => $data['remark'] ?? $enrollment->remark,
                        ])->save();
                    }),
            ])
            ->defaultSort('start_at', 'desc');
    }
}
