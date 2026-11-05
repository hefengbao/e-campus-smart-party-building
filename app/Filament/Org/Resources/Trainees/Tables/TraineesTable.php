<?php

namespace App\Filament\Org\Resources\Trainees\Tables;

use App\Models\Trainee;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class TraineesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('staff_no')
                    ->label('工号')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('姓名')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('gender')
                    ->label('性别')
                    ->toggleable(),
                TextColumn::make('organization.short_name')
                    ->label('所在单位')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('department')
                    ->label('部门')
                    ->toggleable(),
                TextColumn::make('position')
                    ->label('职务')
                    ->toggleable(),
                TextColumn::make('cadre_type')
                    ->label('干部类型')
                    ->badge()
                    ->color('info'),
                TextColumn::make('political_status')
                    ->label('政治面貌')
                    ->toggleable(),
                IconColumn::make('is_party_member')
                    ->label('党员')
                    ->boolean(),
                TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '在训' => 'success',
                        '结业' => 'info',
                        '调离', '退休' => 'gray',
                        default => 'gray',
                    }),
                IconColumn::make('enabled')
                    ->label('启用')
                    ->boolean(),
                TextColumn::make('phone')
                    ->label('联系电话')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('状态')
                    ->options(Trainee::STATUSES),
                SelectFilter::make('cadre_type')
                    ->label('干部类型')
                    ->options(Trainee::CADRE_TYPES),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
