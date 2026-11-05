<?php

namespace App\Filament\Org\Resources\Trainings\Schemas;

use App\Models\Training;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TrainingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('培训信息')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('培训标题')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Select::make('category')
                            ->label('分类')
                            ->options(Training::CATEGORIES)
                            ->default('党员教育')
                            ->required(),
                        Select::make('provider_level')
                            ->label('发布层级')
                            ->options(Training::PROVIDER_LEVELS)
                            ->default('校级')
                            ->required(),
                        TextInput::make('hours_standard')
                            ->label('标准学时')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(999)
                            ->required(),
                        DatePicker::make('start_date')
                            ->label('开始日期'),
                        DatePicker::make('end_date')
                            ->label('结束日期')
                            ->afterOrEqual('start_date'),
                        DatePicker::make('signup_deadline')
                            ->label('报名截止'),
                        Toggle::make('need_signup')
                            ->label('需报名')
                            ->default(true)
                            ->required(),
                        Toggle::make('need_approval')
                            ->label('需审批')
                            ->default(true)
                            ->required(),
                        Select::make('status')
                            ->label('状态')
                            ->options(Training::STATUSES)
                            ->default('报名中')
                            ->required(),
                        Textarea::make('description')
                            ->label('培训说明')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
