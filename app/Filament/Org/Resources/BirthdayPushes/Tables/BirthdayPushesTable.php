<?php

namespace App\Filament\Org\Resources\BirthdayPushes\Tables;

use App\Models\BirthdayPush;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BirthdayPushesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('member.name')
                    ->label('党员')
                    ->searchable(),
                TextColumn::make('member.organization.name')
                    ->label('所在组织')
                    ->limit(20),
                TextColumn::make('birthday_date')
                    ->label('政治生日')
                    ->date('Y-m-d')
                    ->sortable(),
                TextColumn::make('years')
                    ->label('第 N 年'),
                TextColumn::make('channel')
                    ->label('渠道')
                    ->badge()
                    ->color('info'),
                TextColumn::make('content')
                    ->label('推送内容')
                    ->limit(30)
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '待推送' => 'warning',
                        '已推送' => 'success',
                        '失败' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('pushed_at')
                    ->label('推送时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('状态')
                    ->options(BirthdayPush::STATUSES),
                SelectFilter::make('channel')
                    ->label('渠道')
                    ->options(BirthdayPush::CHANNELS),
            ])
            ->defaultSort('birthday_date', 'desc');
    }
}
