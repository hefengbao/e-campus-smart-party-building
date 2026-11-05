<?php

namespace App\Filament\Org\Resources\SupervisionTasks\Tables;

use App\Models\SupervisionTask;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SupervisionTasksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('任务标题')
                    ->searchable()
                    ->limit(30),
                TextColumn::make('organization.name')
                    ->label('责任组织')
                    ->limit(20),
                TextColumn::make('due_date')
                    ->label('截止日期')
                    ->date('Y-m-d')
                    ->sortable()
                    ->color(fn (SupervisionTask $record): string => $record->isOverdue() ? 'danger' : 'gray'),
                TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '进行中' => 'info',
                        '已完成' => 'success',
                        '已逾期' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('completed_at')
                    ->label('完成时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('状态')
                    ->options(SupervisionTask::STATUSES),
            ])
            ->recordActions([
                // 标记完成。
                Action::make('complete')
                    ->label('标记完成')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->schema([
                        Textarea::make('completion_note')
                            ->label('完成说明')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (SupervisionTask $record): bool => $record->status !== '已完成')
                    ->action(function (array $data, SupervisionTask $record): void {
                        $record->forceFill([
                            'status' => '已完成',
                            'completed_at' => now(),
                            'completion_note' => $data['completion_note'] ?? null,
                        ])->save();
                    }),
            ])
            ->defaultSort('due_date');
    }
}
