<?php

namespace App\Filament\SysAdmin\Resources\Configs\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ConfigsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('参数名称')
                    ->searchable(),
                TextColumn::make('key')
                    ->label('参数键')
                    ->badge()
                    ->color('info')
                    ->searchable(),
                TextColumn::make('value')
                    ->label('当前值')
                    ->limit(40),
                TextColumn::make('remark')
                    ->label('备注')
                    ->limit(30)
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label('更新时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->defaultSort('id');
    }
}
