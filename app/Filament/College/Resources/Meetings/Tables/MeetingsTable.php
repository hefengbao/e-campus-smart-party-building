<?php

namespace App\Filament\College\Resources\Meetings\Tables;

use App\Models\Meeting;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class MeetingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('organization.short_name')
                    ->label('召开组织')
                    ->sortable(),
                TextColumn::make('meeting_type')
                    ->label('会议类型')
                    ->badge()
                    ->color('info'),
                TextColumn::make('title')
                    ->label('会议标题')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('actual_at')
                    ->label('召开时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                TextColumn::make('expected_count')
                    ->label('应到')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('attendees_count')
                    ->label('实到')
                    ->counts('attendees')
                    ->alignCenter(),
                TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '草稿' => 'gray',
                        '已提交' => 'warning',
                        '退回修改' => 'danger',
                        '已归档' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('submitted_at')
                    ->label('提交时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('meeting_type')
                    ->label('会议类型')
                    ->options(Meeting::MEETING_TYPES),
                SelectFilter::make('status')
                    ->label('状态')
                    ->options(Meeting::STATUSES),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('archive')
                    ->label('归档')
                    ->icon('heroicon-m-archive-box')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('归档后台账不可再修改。')
                    ->visible(fn (Meeting $record): bool => $record->status === '已提交')
                    ->action(function (Meeting $record): void {
                        $record->update([
                            'status' => '已归档',
                            'reviewed_at' => now(),
                            'reviewed_by' => Auth::id(),
                        ]);

                        $record->logs()->create([
                            'action' => '归档',
                            'operator' => Auth::id(),
                            'role' => '院级',
                        ]);
                    }),
                Action::make('return')
                    ->label('退回修改')
                    ->icon('heroicon-m-arrow-uturn-left')
                    ->color('danger')
                    ->schema([
                        Textarea::make('review_comment')
                            ->label('退回原因')
                            ->required()
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (Meeting $record): bool => $record->status === '已提交')
                    ->action(function (array $data, Meeting $record): void {
                        $record->update([
                            'status' => '退回修改',
                            'reviewed_at' => now(),
                            'reviewed_by' => Auth::id(),
                            'review_comment' => $data['review_comment'],
                        ]);

                        $record->logs()->create([
                            'action' => '退回修改',
                            'operator' => Auth::id(),
                            'role' => '院级',
                            'comment' => $data['review_comment'],
                        ]);
                    }),
            ])
            ->defaultSort('submitted_at', 'desc');
    }
}
