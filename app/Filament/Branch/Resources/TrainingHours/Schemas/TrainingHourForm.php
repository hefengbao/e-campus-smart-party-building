<?php

namespace App\Filament\Branch\Resources\TrainingHours\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TrainingHourForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('staff_no')
                    ->label('学员工号/学号')
                    ->required()
                    ->live()
                    ->helperText('输入学员工号后系统自动匹配在训学员'),
                TextInput::make('title')
                    ->label('培训名称')
                    ->required()
                    ->maxLength(120),
                Select::make('training_category')
                    ->label('学时类别')
                    ->options([
                        '网络自学' => '网络自学',
                        '集中培训' => '集中培训',
                    ])
                    ->required(),
                Select::make('training_form')
                    ->label('培训形式')
                    ->options([
                        '线下培训' => '线下培训',
                        '线上课程' => '线上课程',
                    ])
                    ->required(),
                TextInput::make('hours')
                    ->label('学时数')
                    ->required()
                    ->numeric()
                    ->minValue(0.5)
                    ->maxValue(999),
                DatePicker::make('occurred_on')
                    ->label('培训日期')
                    ->required()
                    ->maxDate(now()),
                TextInput::make('project_name')
                    ->label('项目名称')
                    ->maxLength(120),
                Textarea::make('remark')
                    ->label('备注')
                    ->columnSpanFull(),
            ]);
    }
}
