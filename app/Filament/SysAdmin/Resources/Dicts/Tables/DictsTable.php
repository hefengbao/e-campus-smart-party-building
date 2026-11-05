<?php

namespace App\Filament\SysAdmin\Resources\Dicts\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DictsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label('字典类型')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('label')
                    ->label('显示名称')
                    ->searchable(),
                TextColumn::make('key')
                    ->label('键值')
                    ->searchable(),
                TextColumn::make('sort_order')
                    ->label('排序')
                    ->sortable(),
                IconColumn::make('enabled')
                    ->label('启用')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('字典类型')
                    ->options([
                        'education' => '学历',
                        'identity' => '党员身份',
                        'gender' => '性别',
                        'ethnicity' => '民族',
                        'member_status' => '党籍状态',
                        'data_source' => '数据来源',
                        'committee_branch_position' => '支部委员会职务',
                        'committee_college_position' => '二级党委职务',
                        'appointment_method' => '产生方式',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->defaultSort('type')
            ->defaultSort('sort_order');
    }
}
