<?php

namespace App\Filament\Org\Resources\WorkshopLinks\Tables;

use App\Models\WorkshopLink;
use Filament\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WorkshopLinksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('标题')
                    ->searchable(),
                TextColumn::make('url')
                    ->label('地址')
                    ->limit(40)
                    ->color('primary'),
                TextColumn::make('category')
                    ->label('分类')
                    ->badge()
                    ->color('info'),
                IconColumn::make('is_pinned')
                    ->label('置顶')
                    ->boolean(),
                TextColumn::make('sort')
                    ->label('排序')
                    ->sortable(),
                TextColumn::make('click_count')
                    ->label('点击数')
                    ->sortable(),
                IconColumn::make('enabled')
                    ->label('启用')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('分类')
                    ->options(WorkshopLink::CATEGORIES),
            ])
            ->recordActions([
                Action::make('toggle_enabled')
                    ->label(fn (WorkshopLink $record): string => $record->enabled ? '停用' : '启用')
                    ->icon('heroicon-m-power')
                    ->color(fn (WorkshopLink $record): bool => ! $record->enabled)
                    ->action(function (WorkshopLink $record): void {
                        $record->forceFill(['enabled' => ! $record->enabled])->save();
                    }),
            ])
            ->defaultSort('sort');
    }
}
