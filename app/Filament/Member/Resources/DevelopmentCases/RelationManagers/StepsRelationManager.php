<?php

namespace App\Filament\Member\Resources\DevelopmentCases\RelationManagers;

use App\Models\DevelopmentCase;
use App\Models\DevelopmentStep;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StepsRelationManager extends RelationManager
{
    protected static string $relationship = 'steps';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('step_name')
            ->columns([
                TextColumn::make('stage_no')
                    ->label('阶段')
                    ->alignCenter()
                    ->sortable(),
                TextColumn::make('step_no')
                    ->label('步骤号')
                    ->alignCenter()
                    ->sortable(),
                TextColumn::make('step_name')
                    ->label('步骤名称')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '未开始' => 'gray',
                        '进行中' => 'warning',
                        '已完成' => 'success',
                        '逾期' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('planned_date')
                    ->label('计划完成日期')
                    ->date('Y-m-d')
                    ->toggleable(),
                TextColumn::make('done_date')
                    ->label('实际完成日期')
                    ->date('Y-m-d')
                    ->toggleable(),
                TextColumn::make('materials')
                    ->label('提交材料')
                    ->state(fn (DevelopmentStep $record): string => $record->materials === null || $record->materials === []
                        ? '未提交'
                        : implode('、', $record->materials))
                    ->limit(30),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('状态')
                    ->options(DevelopmentStep::STATUSES),
                SelectFilter::make('stage_no')
                    ->label('阶段')
                    ->options(collect(DevelopmentCase::STAGES)->map(fn (array $stage, int $no): string => $no.'.'.$stage[1])->all()),
            ])
            ->defaultSort('step_no', 'asc');
    }
}
