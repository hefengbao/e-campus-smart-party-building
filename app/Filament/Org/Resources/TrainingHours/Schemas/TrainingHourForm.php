<?php

namespace App\Filament\Org\Resources\TrainingHours\Schemas;

use App\Models\Trainee;
use App\Models\TrainingHour;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TrainingHourForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('学时信息')
                    ->columns(2)
                    ->schema([
                        Select::make('trainee_id')
                            ->label('学员')
                            ->options(fn (): array => Trainee::query()
                                ->where('enabled', true)
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn (Trainee $trainee): array => [
                                    $trainee->id => $trainee->name.'（'.$trainee->staff_no.'）',
                                ])
                                ->all())
                            ->searchable()
                            ->required(),
                        TextInput::make('title')
                            ->label('学时名称')
                            ->required()
                            ->maxLength(255),
                        Select::make('training_category')
                            ->label('学时口径')
                            ->options(TrainingHour::TRAINING_CATEGORIES)
                            ->required()
                            ->helperText('网络自学 / 集中培训'),
                        Select::make('training_form')
                            ->label('培训形式')
                            ->options([
                                '学校学习平台' => '学校学习平台',
                                '其他网络平台' => '其他网络平台',
                                '脱产培训' => '脱产培训',
                                '专题轮训' => '专题轮训',
                                '其他' => '其他',
                            ]),
                        TextInput::make('hours')
                            ->label('学时数')
                            ->numeric()
                            ->minValue(0.5)
                            ->maxValue(999)
                            ->required(),
                        DatePicker::make('occurred_on')
                            ->label('发生日期')
                            ->maxDate(now()),
                        Select::make('year')
                            ->label('年度')
                            ->options(collect(range((int) now()->format('Y'), (int) now()->format('Y') - 5, -1))
                                ->mapWithKeys(fn (int $y): array => [$y => $y])
                                ->all())
                            ->default((int) now()->format('Y'))
                            ->required(),
                        TextInput::make('project_name')
                            ->label('所属培训项目')
                            ->maxLength(255),
                        Textarea::make('remark')
                            ->label('备注')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
