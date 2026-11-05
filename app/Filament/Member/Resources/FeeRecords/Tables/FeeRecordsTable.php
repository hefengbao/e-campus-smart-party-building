<?php

namespace App\Filament\Member\Resources\FeeRecords\Tables;

use App\Models\FeeRecord;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FeeRecordsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('period')
                    ->label('所属月份')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('应交金额（元）')
                    ->money('CNY')
                    ->sortable(),
                TextColumn::make('pay_status')
                    ->label('交纳状态')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '已交纳' => 'success',
                        '未交纳' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('pay_date')
                    ->label('交纳日期')
                    ->date('Y-m-d')
                    ->placeholder('未交纳')
                    ->sortable(),
                TextColumn::make('source')
                    ->label('数据来源')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('pay_status')
                    ->label('交纳状态')
                    ->options(FeeRecord::PAY_STATUSES),
            ])
            ->defaultSort('period', 'desc');
    }
}
