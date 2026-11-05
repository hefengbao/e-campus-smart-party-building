<?php

namespace App\Filament\Branch\Resources\DevelopmentCases\RelationManagers;

use App\Models\DevelopmentCase;
use App\Models\DevelopmentStep;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

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
                    ->label('步骤')
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
                    ->label('计划日期')
                    ->date('Y-m-d')
                    ->toggleable(),
                TextColumn::make('done_date')
                    ->label('完成日期')
                    ->date('Y-m-d')
                    ->toggleable(),
                TextColumn::make('materials')
                    ->label('材料')
                    ->state(fn (DevelopmentStep $record): string => $record->materials === null ? '—' : count($record->materials).' 份')
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('状态')
                    ->options(DevelopmentStep::STATUSES),
                SelectFilter::make('stage_no')
                    ->label('阶段')
                    ->options(collect(DevelopmentCase::STAGES)->map(fn (array $stage, int $no): string => $no.'.'.$stage[1])->all()),
            ])
            ->recordActions([
                Action::make('start')
                    ->label('开始')
                    ->icon('heroicon-m-play')
                    ->color('warning')
                    ->visible(fn (DevelopmentStep $record): bool => $record->status === '未开始')
                    ->action(function (DevelopmentStep $record): void {
                        $record->update(['status' => '进行中']);
                    }),
                Action::make('complete')
                    ->label('完成')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->schema([
                        Section::make('步骤完成登记')
                            ->columns(2)
                            ->schema([
                                DatePicker::make('done_date')
                                    ->label('完成日期')
                                    ->default(now())
                                    ->required(),
                                FileUpload::make('materials')
                                    ->label('材料附件')
                                    ->disk('public')
                                    ->directory('development-materials')
                                    ->multiple()
                                    ->maxSize(20480)
                                    ->columnSpanFull(),
                                Textarea::make('opinion')
                                    ->label('意见/结论')
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->visible(fn (DevelopmentStep $record): bool => $record->status !== '已完成')
                    ->action(function (array $data, DevelopmentStep $record): void {
                        $record->update([
                            'status' => '已完成',
                            'done_date' => $data['done_date'],
                            'opinion' => $data['opinion'] ?? null,
                            'materials' => $data['materials'] ?? null,
                            'operator' => Auth::id(),
                        ]);

                        $this->advanceCase($record);
                    }),
            ])
            ->defaultSort('step_no');
    }

    protected function advanceCase(DevelopmentStep $step): void
    {
        /** @var DevelopmentCase $case */
        $case = $this->getOwnerRecord();

        if ($step->step_no >= 25) {
            $case->update([
                'status' => '已完成',
                'finished_at' => now(),
            ]);

            return;
        }

        $next = $case->steps()
            ->where('step_no', '>', $step->step_no)
            ->orderBy('step_no')
            ->first();

        if ($next !== null) {
            $case->update([
                'stage_no' => $next->stage_no,
                'step_no' => $next->step_no,
            ]);
        }
    }
}
