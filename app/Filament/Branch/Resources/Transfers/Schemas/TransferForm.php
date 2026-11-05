<?php

namespace App\Filament\Branch\Resources\Transfers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TransferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('member_id')
                    ->label('转出党员')
                    ->relationship('member', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('transfer_type')
                    ->label('转接类型')
                    ->options([
                        '校外' => '校外',
                        '校内' => '校内',
                    ])
                    ->default('校外')
                    ->required(),
                TextInput::make('to_org_name')
                    ->label('转入组织名称')
                    ->required()
                    ->maxLength(120),
                TextInput::make('to_org_code')
                    ->label('转入组织代码')
                    ->maxLength(30),
                Textarea::make('reason')
                    ->label('转接原因')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }
}
