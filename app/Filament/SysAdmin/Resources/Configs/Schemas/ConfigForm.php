<?php

namespace App\Filament\SysAdmin\Resources\Configs\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ConfigForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('key')
                ->label('参数键 (Key)')
                ->required()
                ->maxLength(100)
                ->unique(ignoreRecord: true),
            TextInput::make('name')
                ->label('参数名称')
                ->required()
                ->maxLength(100),
            Textarea::make('value')
                ->label('参数值')
                ->rows(3)
                ->columnSpanFull(),
            TextInput::make('remark')
                ->label('备注说明')
                ->maxLength(200)
                ->columnSpanFull(),
        ]);
    }
}
