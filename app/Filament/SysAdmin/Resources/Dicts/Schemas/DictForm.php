<?php

namespace App\Filament\SysAdmin\Resources\Dicts\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DictForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('type')
                ->label('字典类型 (Type)')
                ->required()
                ->maxLength(50),
            TextInput::make('key')
                ->label('键 (Key)')
                ->required()
                ->maxLength(50),
            TextInput::make('label')
                ->label('中文标签 (Label)')
                ->required()
                ->maxLength(100),
            TextInput::make('sort_order')
                ->label('排序权重')
                ->numeric()
                ->default(0)
                ->required(),
            Toggle::make('enabled')
                ->label('是否启用')
                ->default(true)
                ->required(),
        ]);
    }
}
