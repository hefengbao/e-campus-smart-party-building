<?php

namespace App\Filament\Branch\Resources\Meetings\RelationManagers;

use App\Models\MeetingAttendee;
use App\Models\Member;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AttendeesRelationManager extends RelationManager
{
    protected static string $relationship = 'attendees';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('member_id')
                    ->label('参会党员')
                    ->options(fn (RelationManager $livewire): array => Member::query()
                        ->visibleTo([$livewire->getOwnerRecord()->org_id])
                        ->where('status', '正常')
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->required()
                    ->disabled(fn (?MeetingAttendee $record): bool => $record !== null),
                Section::make('考勤')
                    ->columns(2)
                    ->schema([
                        Select::make('confirm_status')
                            ->label('确认状态')
                            ->options(MeetingAttendee::CONFIRM_STATUSES)
                            ->native(false)
                            ->default('待确认'),
                        Select::make('attendance_status')
                            ->label('考勤状态')
                            ->options(MeetingAttendee::ATTENDANCE_STATUSES)
                            ->native(false)
                            ->default('未签到'),
                        TextInput::make('sign_in_method')
                            ->label('签到方式')
                            ->default('后台录入')
                            ->disabled(),
                        Textarea::make('leave_reason')
                            ->label('请假原因')
                            ->columnSpanFull()
                            ->visible(fn ($get): bool => $get('attendance_status') === '请假' || $get('confirm_status') === '请假'),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('member_id')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('member'))
            ->columns([
                TextColumn::make('member.name')
                    ->label('姓名')
                    ->sortable(),
                TextColumn::make('member.staff_no')
                    ->label('工号/学号')
                    ->sortable(),
                TextColumn::make('confirm_status')
                    ->label('确认状态')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '参加' => 'success',
                        '请假' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('attendance_status')
                    ->label('考勤状态')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '出勤' => 'success',
                        '迟到' => 'warning',
                        '请假', '缺席' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('sign_in_at')
                    ->label('签到时间')
                    ->dateTime('H:i:s')
                    ->placeholder('—'),
                TextColumn::make('leave_reason')
                    ->label('请假原因')
                    ->limit(20)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->after(function (): void {
                        // 新增考勤后刷新应到人数（应到 = 本支部正式党员数）。
                        $this->getOwnerRecord()->update([
                            'expected_count' => Member::query()
                                ->where('org_id', $this->getOwnerRecord()->org_id)
                                ->where('status', '正常')
                                ->count(),
                        ]);
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('member_id');
    }
}
