<?php

namespace App\Filament\Org\Resources\SupervisionTasks\Schemas;

use App\Models\Organization;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SupervisionTaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('任务标题')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Select::make('org_id')
                    ->label('责任组织')
                    ->options(fn (): array => Organization::query()
                        ->where('level', '>=', 2)
                        ->orderBy('path')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->required(),
                DatePicker::make('due_date')
                    ->label('截止日期')
                    ->required(),
                Textarea::make('content')
                    ->label('任务内容')
                    ->rows(4)
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }
}
