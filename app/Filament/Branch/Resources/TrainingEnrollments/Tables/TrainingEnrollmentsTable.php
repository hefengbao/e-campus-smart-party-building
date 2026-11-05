<?php

namespace App\Filament\Branch\Resources\TrainingEnrollments\Tables;

use App\Models\TrainingEnrollment;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class TrainingEnrollmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('training.title')
                    ->label('培训项目')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('member.name')
                    ->label('报名党员')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '待支部审核' => 'warning',
                        '待院级审核' => 'info',
                        '已通过' => 'success',
                        '已驳回' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('applied_at')
                    ->label('报名时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                TextColumn::make('branch_reviewed_at')
                    ->label('支部审核时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('remark')
                    ->label('备注')
                    ->limit(20)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('状态')
                    ->options(TrainingEnrollment::STATUSES),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('通过')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('通过后将流转至院级党组织审核。')
                    ->visible(fn (TrainingEnrollment $record): bool => $record->status === '待支部审核')
                    ->action(function (TrainingEnrollment $record): void {
                        $record->reviewBranch(approve: true, reviewerId: (int) Auth::id());
                    })
                    ->successNotificationTitle('已通过，待院级审核'),
                Action::make('reject')
                    ->label('驳回')
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->visible(fn (TrainingEnrollment $record): bool => $record->status === '待支部审核')
                    ->schema([
                        Textarea::make('note')
                            ->label('驳回原因')
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->action(function (array $data, TrainingEnrollment $record): void {
                        $record->reviewBranch(approve: false, reviewerId: (int) Auth::id(), note: $data['note']);
                    })
                    ->successNotificationTitle('已驳回'),
            ])
            ->defaultSort('applied_at', 'desc');
    }
}
