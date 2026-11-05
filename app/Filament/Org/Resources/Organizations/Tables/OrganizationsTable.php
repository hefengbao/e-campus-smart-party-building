<?php

namespace App\Filament\Org\Resources\Organizations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class OrganizationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('编码')
                    ->searchable()
                    ->copyable()
                    ->width('110px'),
                TextColumn::make('short_name')
                    ->label('简称')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('full_name')
                    ->label('全称')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('level')
                    ->label('层级')
                    ->badge()
                    ->formatStateUsing(fn (int $state): string => [
                        1 => '学校党委',
                        2 => '二级党委',
                        3 => '党支部',
                    ][$state] ?? '未知')
                    ->color(fn (int $state): string => match ($state) {
                        1 => 'danger',
                        2 => 'warning',
                        default => 'info',
                    })
                    ->sortable(),
                TextColumn::make('secretary')
                    ->label('书记')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('member_type')
                    ->label('成员类型')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => [
                        'tea' => '教工',
                        'stu' => '学生',
                        'mix' => '混合',
                    ][$state] ?? '—')
                    ->color(fn (?string $state): string => match ($state) {
                        'tea' => 'success',
                        'stu' => 'primary',
                        'mix' => 'gray',
                        default => 'gray',
                    })
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '正常' => 'success',
                        '撤销' => 'danger',
                        '合并' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->label('排序')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('founded_date')
                    ->label('成立日期')
                    ->date('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('term_end_date')
                    ->label('任期结束')
                    ->date('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('更新时间')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->label('删除时间')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('level')
                    ->label('层级')
                    ->options([
                        1 => '学校党委',
                        2 => '二级党委',
                        3 => '党支部',
                    ]),
                SelectFilter::make('status')
                    ->label('状态')
                    ->options([
                        '正常' => '正常',
                        '撤销' => '撤销',
                        '合并' => '合并',
                    ]),
                SelectFilter::make('member_type')
                    ->label('成员类型')
                    ->options([
                        'tea' => '教工',
                        'stu' => '学生',
                        'mix' => '混合',
                    ]),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order');
    }
}
