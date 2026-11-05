<?php

namespace App\Filament\Branch\Resources\DevelopmentCases\Tables;

use App\Models\DevelopmentCase;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DevelopmentCasesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('member.name')
                    ->label('姓名')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('member.staff_no')
                    ->label('工号/学号')
                    ->sortable(),
                TextColumn::make('organization.short_name')
                    ->label('所在组织')
                    ->toggleable(),
                TextColumn::make('stage_no')
                    ->label('阶段')
                    ->formatStateUsing(fn (int $state): string => $state.'.'.DevelopmentCase::STAGES[$state][1])
                    ->badge()
                    ->color('info'),
                TextColumn::make('step_no')
                    ->label('当前步骤')
                    ->formatStateUsing(fn (DevelopmentCase $record): string => $record->step_no.'/25')
                    ->alignCenter(),
                TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '进行中' => 'warning',
                        '已完成' => 'success',
                        '已终止' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('overdue')
                    ->label('超期')
                    ->state(fn (DevelopmentCase $record): string => $record->isOverdue() ? '超期' : '正常')
                    ->badge()
                    ->color(fn (string $state): string => $state === '超期' ? 'danger' : 'success'),
                TextColumn::make('apply_date')
                    ->label('申请日期')
                    ->date('Y-m-d')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('finished_at')
                    ->label('办结时间')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('状态')
                    ->options(DevelopmentCase::STATUSES),
                SelectFilter::make('stage_no')
                    ->label('阶段')
                    ->options(collect(DevelopmentCase::STAGES)->map(fn (array $stage, int $no): string => $no.'.'.$stage[1])->all()),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (DevelopmentCase $record): bool => ! $record->isClosed()),
                Action::make('terminate')
                    ->label('终止')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->schema([
                        Textarea::make('remark')
                            ->label('终止原因')
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (DevelopmentCase $record): bool => ! $record->isClosed())
                    ->action(function (array $data, DevelopmentCase $record): void {
                        $record->update([
                            'status' => '已终止',
                            'finished_at' => now(),
                            'remark' => $data['remark'],
                        ]);
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
