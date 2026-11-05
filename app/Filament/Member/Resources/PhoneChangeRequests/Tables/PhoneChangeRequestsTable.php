<?php

namespace App\Filament\Member\Resources\PhoneChangeRequests\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PhoneChangeRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('申请时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                TextColumn::make('old_phone')
                    ->label('原联系电话'),
                TextColumn::make('new_phone')
                    ->label('新联系电话')
                    ->badge()
                    ->color('info'),
                TextColumn::make('status')
                    ->label('审核状态')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '待审核' => 'warning',
                        '已通过' => 'success',
                        '已驳回' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('review_note')
                    ->label('审核说明')
                    ->limit(30)
                    ->toggleable(),
                TextColumn::make('reviewed_at')
                    ->label('审核时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('状态')
                    ->options(['待审核' => '待审核', '已通过' => '已通过', '已驳回' => '已驳回']),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
