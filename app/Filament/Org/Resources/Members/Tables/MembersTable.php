<?php

namespace App\Filament\Org\Resources\Members\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class MembersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('姓名')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('gender')
                    ->label('性别')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '男' => 'info',
                        '女' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('masked_id_card')
                    ->label('身份证号')
                    ->state(fn ($record): string => $record->masked_id_card)
                    ->copyable()
                    ->copyMessage('已复制脱敏身份证号'),
                TextColumn::make('staff_no')
                    ->label('工号/学号')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('organization.short_name')
                    ->label('所在党支部')
                    ->sortable(),
                TextColumn::make('identity')
                    ->label('身份')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '正式党员' => 'success',
                        '预备党员' => 'warning',
                        default => 'gray',
                    })
                    ->placeholder('—'),
                TextColumn::make('join_party_date')
                    ->label('入党时间')
                    ->date('Y-m-d')
                    ->sortable(),
                TextColumn::make('full_member_date')
                    ->label('转正时间')
                    ->date('Y-m-d')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('relation_in_school')
                    ->label('在校关系')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('phone')
                    ->label('联系电话')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '正常' => 'success',
                        '转出' => 'info',
                        '停止党籍', '开除党籍', '死亡' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('data_source')
                    ->label('数据来源')
                    ->badge()
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
                SelectFilter::make('identity')
                    ->label('身份')
                    ->options([
                        '正式党员' => '正式党员',
                        '预备党员' => '预备党员',
                        '入党积极分子' => '入党积极分子',
                    ]),
                SelectFilter::make('status')
                    ->label('状态')
                    ->options([
                        '正常' => '正常',
                        '停止党籍' => '停止党籍',
                        '开除党籍' => '开除党籍',
                        '死亡' => '死亡',
                        '转出' => '转出',
                    ]),
                SelectFilter::make('relation_in_school')
                    ->label('在校关系')
                    ->options(['教职工' => '教职工', '学生' => '学生']),
                SelectFilter::make('organization')
                    ->label('所在党支部')
                    ->relationship('organization', 'short_name')
                    ->searchable()
                    ->preload(),
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
            ->defaultSort('staff_no');
    }
}
