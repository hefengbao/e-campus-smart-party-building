<?php

namespace App\Filament\Member\Resources\Transfers\Tables;

use App\Models\Transfer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TransfersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('direction')
                    ->label('方向')
                    ->badge()
                    ->color(fn (string $state): string => $state === '转出' ? 'warning' : 'success'),
                TextColumn::make('transfer_type')
                    ->label('类型')
                    ->badge()
                    ->color('info'),
                TextColumn::make('to_org_name')
                    ->label('去向组织')
                    ->limit(30),
                TextColumn::make('apply_at')
                    ->label('申请时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '待支部审核' => 'warning',
                        '待院级审核', '待组织部审核' => 'info',
                        '已办结' => 'success',
                        '已驳回' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('letter_no')
                    ->label('介绍信编号')
                    ->toggleable(),
                TextColumn::make('remark')
                    ->label('备注')
                    ->limit(30)
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('状态')
                    ->options(Transfer::STATUSES),
            ])
            ->recordActions([])
            ->defaultSort('apply_at', 'desc');
    }
}
