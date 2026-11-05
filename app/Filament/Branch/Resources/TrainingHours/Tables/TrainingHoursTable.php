<?php

namespace App\Filament\Branch\Resources\TrainingHours\Tables;

use App\Models\TrainingHour;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TrainingHoursTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('member.name')
                    ->label('学员')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title')
                    ->label('培训名称')
                    ->searchable()
                    ->limit(30),
                TextColumn::make('training_category')
                    ->label('学时类别')
                    ->badge()
                    ->color('info'),
                TextColumn::make('training_form')
                    ->label('培训形式')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),
                TextColumn::make('hours')
                    ->label('学时数')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('occurred_on')
                    ->label('培训日期')
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
                    ->label('审核备注')
                    ->limit(20)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('状态')
                    ->options(TrainingHour::STATUSES),
                SelectFilter::make('training_category')
                    ->label('学时类别')
                    ->options(TrainingHour::TRAINING_CATEGORIES),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (TrainingHour $record): bool => $record->status === '待审核'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
