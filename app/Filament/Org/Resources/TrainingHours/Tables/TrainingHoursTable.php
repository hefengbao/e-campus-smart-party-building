<?php

namespace App\Filament\Org\Resources\TrainingHours\Tables;

use App\Models\TrainingHour;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class TrainingHoursTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('trainee.name')
                    ->label('学员')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('trainee.staff_no')
                    ->label('工号')
                    ->sortable(),
                TextColumn::make('title')
                    ->label('学时名称')
                    ->searchable(),
                TextColumn::make('training_category')
                    ->label('口径')
                    ->badge()
                    ->color(fn (string $state): string => $state === '网络自学' ? 'info' : 'warning'),
                TextColumn::make('training_form')
                    ->label('形式')
                    ->toggleable(),
                TextColumn::make('hours')
                    ->label('学时')
                    ->alignCenter()
                    ->sortable(),
                TextColumn::make('year')
                    ->label('年度')
                    ->alignCenter()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '已生效' => 'success',
                        '待审核' => 'warning',
                        '已驳回' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('occurred_on')
                    ->label('发生日期')
                    ->date('Y-m-d')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('review_note')
                    ->label('审核意见')
                    ->limit(20)
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('状态')
                    ->options(TrainingHour::STATUSES),
                SelectFilter::make('training_category')
                    ->label('口径')
                    ->options(TrainingHour::TRAINING_CATEGORIES),
                SelectFilter::make('year')
                    ->label('年度')
                    ->options(collect(range((int) now()->format('Y'), (int) now()->format('Y') - 4, -1))
                        ->mapWithKeys(fn (int $y): array => [$y => $y])
                        ->all()),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (TrainingHour $record): bool => $record->status !== '待审核'),
                Action::make('approve')
                    ->label('通过')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (TrainingHour $record): bool => $record->status === '待审核')
                    ->action(function (TrainingHour $record): void {
                        $record->update([
                            'status' => '已生效',
                            'reviewer' => Auth::id(),
                            'reviewed_at' => now(),
                        ]);
                    }),
                Action::make('reject')
                    ->label('驳回')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->visible(fn (TrainingHour $record): bool => $record->status === '待审核')
                    ->schema([
                        Textarea::make('review_note')
                            ->label('驳回原因')
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->action(function (array $data, TrainingHour $record): void {
                        $record->update([
                            'status' => '已驳回',
                            'review_note' => $data['review_note'],
                            'reviewer' => Auth::id(),
                            'reviewed_at' => now(),
                        ]);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
