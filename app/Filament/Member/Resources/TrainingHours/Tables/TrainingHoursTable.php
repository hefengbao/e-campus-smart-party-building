<?php

namespace App\Filament\Member\Resources\TrainingHours\Tables;

use App\Models\TrainingHour;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TrainingHoursTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('培训名称')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('training_category')
                    ->label('学时口径')
                    ->badge()
                    ->color(fn (string $state): string => $state === '网络自学' ? 'info' : 'success'),
                TextColumn::make('training_form')
                    ->label('培训形式')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('hours')
                    ->label('学时')
                    ->numeric()
                    ->alignCenter()
                    ->sortable(),
                TextColumn::make('occurred_on')
                    ->label('发生日期')
                    ->date('Y-m-d')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '已生效' => 'success',
                        '待审核' => 'warning',
                        '已驳回' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('review_note')
                    ->label('审核意见')
                    ->limit(30)
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('training_category')
                    ->label('学时口径')
                    ->options(TrainingHour::TRAINING_CATEGORIES),
                SelectFilter::make('status')
                    ->label('状态')
                    ->options(TrainingHour::STATUSES),
            ])
            ->defaultSort('occurred_on', 'desc');
    }
}
