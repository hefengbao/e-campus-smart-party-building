<?php

namespace App\Filament\Member\Resources\WorkshopActivities\Tables;

use App\Models\WorkshopActivity;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WorkshopActivitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('活动主题')
                    ->limit(35)
                    ->searchable(),
                TextColumn::make('category')
                    ->label('分类')
                    ->badge()
                    ->color('info'),
                TextColumn::make('organization.name')
                    ->label('主办党组织')
                    ->limit(20),
                TextColumn::make('start_at')
                    ->label('活动时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                TextColumn::make('location')
                    ->label('活动地点')
                    ->limit(20),
                TextColumn::make('effective_status')
                    ->label('状态')
                    ->state(fn (WorkshopActivity $record): string => $record->effectiveStatus())
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '报名中' => 'success',
                        '进行中' => 'warning',
                        '报名截止' => 'gray',
                        '已结束' => 'gray',
                        default => 'info',
                    }),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('活动分类')
                    ->options(WorkshopActivity::CATEGORIES),
            ])
            ->recordActions([
                ViewAction::make()->label('活动详情'),
            ])
            ->defaultSort('start_at', 'desc');
    }
}
