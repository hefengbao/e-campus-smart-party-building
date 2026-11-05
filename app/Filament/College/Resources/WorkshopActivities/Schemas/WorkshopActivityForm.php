<?php

namespace App\Filament\College\Resources\WorkshopActivities\Schemas;

use App\Models\WorkshopActivity;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class WorkshopActivityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('活动标题')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Select::make('category')
                    ->label('活动分类')
                    ->options(WorkshopActivity::CATEGORIES)
                    ->default('党务培训')
                    ->required(),
                TextInput::make('location')
                    ->label('活动地点')
                    ->maxLength(255),
                DateTimePicker::make('start_at')
                    ->label('活动开始时间')
                    ->required()
                    ->seconds(false),
                DateTimePicker::make('end_at')
                    ->label('活动结束时间')
                    ->required()
                    ->after('start_at')
                    ->seconds(false),
                DateTimePicker::make('enroll_deadline')
                    ->label('报名截止时间')
                    ->before('start_at')
                    ->seconds(false),
                TextInput::make('max_enrollments')
                    ->label('报名上限')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->helperText('0 表示不限名额'),
                Toggle::make('is_public')
                    ->label('是否公开（跨院可见）')
                    ->default(true)
                    ->helperText('公开活动需组织部审核后发布；不公开活动保存即发布'),
                Toggle::make('cross_college')
                    ->label('允许跨院报名')
                    ->default(true),
                Textarea::make('description')
                    ->label('活动说明')
                    ->rows(5)
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }
}
