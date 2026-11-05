<?php

namespace App\Filament\Org\Resources\WorkshopLinks\Schemas;

use App\Models\WorkshopLink;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class WorkshopLinkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('链接标题')
                    ->required()
                    ->maxLength(255),
                TextInput::make('url')
                    ->label('链接地址')
                    ->required()
                    ->url()
                    ->maxLength(500),
                Select::make('category')
                    ->label('分类')
                    ->options(WorkshopLink::CATEGORIES)
                    ->default('常用工具')
                    ->required(),
                TextInput::make('sort')
                    ->label('排序')
                    ->numeric()
                    ->default(0)
                    ->helperText('数值越小越靠前'),
                Toggle::make('is_pinned')
                    ->label('置顶'),
                Toggle::make('enabled')
                    ->label('启用')
                    ->default(true),
            ]);
    }
}
