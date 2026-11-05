<?php

namespace App\Filament\Branch\Resources\PhoneChangeRequests\Tables;

use App\Models\PhoneChangeRequest;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class PhoneChangeRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('member.name')
                    ->label('党员')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('member.staff_no')
                    ->label('工号/学号')
                    ->toggleable(),
                TextColumn::make('old_phone')
                    ->label('原手机号')
                    ->searchable(),
                TextColumn::make('new_phone')
                    ->label('新手机号')
                    ->searchable(),
                TextColumn::make('reason')
                    ->label('变更原因')
                    ->limit(30)
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '待审核' => 'warning',
                        '已通过' => 'success',
                        '已驳回' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('review_note')
                    ->label('审核意见')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('reviewed_at')
                    ->label('审核时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('状态')
                    ->options(['待审核' => '待审核', '已通过' => '已通过', '已驳回' => '已驳回']),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('通过')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('通过后将立即更新该党员的手机号。')
                    ->visible(fn (PhoneChangeRequest $record): bool => $record->status === '待审核')
                    ->action(function (PhoneChangeRequest $record): void {
                        $record->member->update(['phone' => $record->new_phone]);

                        $record->update([
                            'status' => '已通过',
                            'reviewed_by' => Auth::id(),
                            'reviewed_at' => now(),
                        ]);
                    }),
                Action::make('reject')
                    ->label('驳回')
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->schema([
                        Textarea::make('review_note')
                            ->label('驳回原因')
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (PhoneChangeRequest $record): bool => $record->status === '待审核')
                    ->action(function (array $data, PhoneChangeRequest $record): void {
                        $record->update([
                            'status' => '已驳回',
                            'review_note' => $data['review_note'],
                            'reviewed_by' => Auth::id(),
                            'reviewed_at' => now(),
                        ]);
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
