<?php

namespace App\Filament\Org\Resources\WorkshopActivities\Tables;

use App\Models\WorkshopActivity;
use App\Notifications\WorkshopActivityReviewed;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
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
                TextColumn::make('organization.name')
                    ->label('发布组织')
                    ->limit(20),
                TextColumn::make('category')
                    ->label('分类')
                    ->badge()
                    ->color('info'),
                TextColumn::make('start_at')
                    ->label('开始时间')
                    ->dateTime('m-d H:i')
                    ->sortable(),
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
                TextColumn::make('created_at')
                    ->label('提交时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('流程状态')
                    ->options(WorkshopActivity::STATUSES),
            ])
            ->recordActions([
                // 审核通过：公开活动发布并通知发布人。
                Action::make('approve')
                    ->label('通过')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('审核通过后活动立即发布，并通知发布人。')
                    ->visible(fn (WorkshopActivity $record): bool => $record->status === '待审核')
                    ->action(function (WorkshopActivity $record): void {
                        $record->forceFill([
                            'status' => '已发布',
                            'reviewed_by' => (string) Auth::id(),
                            'reviewed_at' => now(),
                            'published_at' => now(),
                            'reject_reason' => null,
                        ])->save();

                        $record->createdBy?->notify(
                            new WorkshopActivityReviewed($record, true),
                        );
                    }),
                // 审核驳回：必填原因并通知发布人。
                Action::make('reject')
                    ->label('驳回')
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->schema([
                        Textarea::make('reason')
                            ->label('驳回原因')
                            ->required()
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (WorkshopActivity $record): bool => $record->status === '待审核')
                    ->action(function (array $data, WorkshopActivity $record): void {
                        $record->forceFill([
                            'status' => '已驳回',
                            'reject_reason' => $data['reason'],
                            'reviewed_by' => (string) Auth::id(),
                            'reviewed_at' => now(),
                        ])->save();

                        $record->createdBy?->notify(
                            new WorkshopActivityReviewed($record, false, $data['reason']),
                        );
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
