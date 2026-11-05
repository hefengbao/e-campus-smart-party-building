<?php

namespace App\Filament\Member\Resources\DevelopmentCases\Tables;

use App\Models\DevelopmentCase;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DevelopmentCasesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('stage_no')
                    ->label('阶段')
                    ->formatStateUsing(fn (int $state): string => '第 '.$state.' 阶段：'.(DevelopmentCase::STAGES[$state][1] ?? ''))
                    ->badge()
                    ->color('info'),
                TextColumn::make('step_no')
                    ->label('当前步骤')
                    ->formatStateUsing(fn (DevelopmentCase $record): string => '第 '.$record->step_no.' 步：'.($record->currentStep()?->step_name ?? '—'))
                    ->weight('bold'),
                TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '进行中' => 'warning',
                        '已完成' => 'success',
                        '已终止' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('apply_date')
                    ->label('递交申请日期')
                    ->date('Y-m-d')
                    ->sortable(),
                TextColumn::make('organization.name')
                    ->label('负责组织')
                    ->limit(25),
                TextColumn::make('is_overdue')
                    ->label('进度状态')
                    ->state(fn (DevelopmentCase $record): string => $record->isOverdue() ? '步骤已超期' : '正常推进')
                    ->badge()
                    ->color(fn (string $state): string => $state === '步骤已超期' ? 'danger' : 'success'),
            ])
            ->recordActions([
                ViewAction::make()->label('查看全流程步骤'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
