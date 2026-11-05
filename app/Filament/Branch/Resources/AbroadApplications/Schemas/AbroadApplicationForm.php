<?php

namespace App\Filament\Branch\Resources\AbroadApplications\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AbroadApplicationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('member_id')
                    ->label('申请党员')
                    ->relationship('member', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('destination')
                    ->label('前往国家/地区')
                    ->required()
                    ->maxLength(60),
                DatePicker::make('start_date')
                    ->label('开始日期')
                    ->required(),
                DatePicker::make('end_date')
                    ->label('结束日期')
                    ->required()
                    ->after('start_date'),
                Textarea::make('reason')
                    ->label('事由')
                    ->required()
                    ->columnSpanFull(),
                Toggle::make('retain_relation')
                    ->label('保留组织关系')
                    ->default(true)
                    ->required(),
                TextInput::make('contact_phone')
                    ->label('联系方式')
                    ->tel()
                    ->maxLength(30),
            ]);
    }
}
