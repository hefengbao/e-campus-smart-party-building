<?php

namespace App\Filament\Member\Resources\WorkshopPosts\Schemas;

use App\Models\WorkshopPost;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class WorkshopPostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('问题标题')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Select::make('tag')
                    ->label('标签（必填单选）')
                    ->options(WorkshopPost::TAGS)
                    ->required(),
                Textarea::make('content')
                    ->label('问题正文')
                    ->required()
                    ->rows(6)
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }
}
