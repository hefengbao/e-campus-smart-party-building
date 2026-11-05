<?php

namespace App\Filament\Org\Resources\Trainings\Tables;

use App\Models\Training;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TrainingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('培训标题')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category')
                    ->label('分类')
                    ->badge()
                    ->color('info'),
                TextColumn::make('organization.short_name')
                    ->label('发布组织')
                    ->toggleable(),
                TextColumn::make('provider_level')
                    ->label('层级')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('hours_standard')
                    ->label('标准学时')
                    ->alignCenter(),
                TextColumn::make('start_date')
                    ->label('开始')
                    ->date('Y-m-d')
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label('结束')
                    ->date('Y-m-d')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '草稿' => 'gray',
                        '报名中' => 'warning',
                        '进行中' => 'info',
                        '已结束' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('checkin_code')
                    ->label('签到码')
                    ->placeholder('—')
                    ->copyable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('状态')
                    ->options(Training::STATUSES),
                SelectFilter::make('category')
                    ->label('分类')
                    ->options(Training::CATEGORIES),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('checkin-code')
                    ->label('签到码')
                    ->icon('heroicon-m-qr-code')
                    ->color('success')
                    ->visible(fn (Training $record): bool => $record->status === '进行中')
                    ->action(function (Training $record): void {
                        $record->refreshCheckinCode();
                    })
                    ->successNotificationTitle('签到码已刷新，30 分钟内有效'),
                Action::make('finish')
                    ->label('结束培训')
                    ->icon('heroicon-m-flag')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Training $record): bool => in_array($record->status, ['报名中', '进行中'], true))
                    ->action(function (Training $record): void {
                        $record->update(['status' => '已结束']);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
