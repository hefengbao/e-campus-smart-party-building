<?php

namespace App\Filament\Branch\Resources\Meetings\Tables;

use App\Models\Meeting;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
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
                TextColumn::make('location')
                    ->label('地点')
                    ->toggleable(),
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
                TextColumn::make('review_comment')
                    ->label('审核意见')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                EditAction::make()
                    ->visible(fn (Meeting $record): bool => $record->isSubmittable()),
                Action::make('submit')
                    ->label('提交审核')
                    ->icon('heroicon-m-paper-airplane')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription('提交后院级党组织将进行审核，提交前请确认台账、考勤与附件已完善。')
                    ->visible(fn (Meeting $record): bool => $record->isSubmittable())
                    ->action(function (Meeting $record): void {
                        $record->update([
                            'status' => '已提交',
                            'submitted_at' => now(),
                            'submitted_by' => Auth::id(),
                        ]);

                        $record->logs()->create([
                            'action' => '提交',
                            'operator' => Auth::id(),
                            'role' => '支部',
                        ]);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('actual_at', 'desc');
    }
}
