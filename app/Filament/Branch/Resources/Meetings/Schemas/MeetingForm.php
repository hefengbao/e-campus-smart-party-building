<?php

namespace App\Filament\Branch\Resources\Meetings\Schemas;

use App\Models\Meeting;
use App\Support\DataScope;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class MeetingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('会议信息')
                    ->columns(2)
                    ->schema([
                        Select::make('org_id')
                            ->label('召开组织')
                            ->relationship(
                                name: 'organization',
                                titleAttribute: 'short_name',
                                modifyQueryUsing: fn ($query) => DataScope::applyToOrganizations(
                                    $query,
                                    Auth::user(),
                                ),
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn (?Meeting $record): bool => $record !== null && ! $record->isSubmittable()),
                        Select::make('meeting_type')
                            ->label('会议类型')
                            ->options(Meeting::MEETING_TYPES)
                            ->native(false)
                            ->required(),
                        TextInput::make('title')
                            ->label('会议标题')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        DateTimePicker::make('planned_at')
                            ->label('计划时间'),
                        DateTimePicker::make('actual_at')
                            ->label('实际召开时间'),
                        TextInput::make('location')
                            ->label('地点')
                            ->maxLength(128),
                        TextInput::make('host')
                            ->label('主持人')
                            ->maxLength(64),
                        TextInput::make('recorder')
                            ->label('记录人')
                            ->maxLength(64),
                        Textarea::make('observers')
                            ->label('列席人员')
                            ->placeholder('每行一人')
                            ->columnSpanFull(),
                    ]),
                Section::make('会议内容')
                    ->schema([
                        Textarea::make('summary')
                            ->label('会议纪要')
                            ->rows(6)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
