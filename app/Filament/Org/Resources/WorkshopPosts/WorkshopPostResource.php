<?php

namespace App\Filament\Org\Resources\WorkshopPosts;

use App\Filament\Org\Resources\WorkshopPosts\Pages\ListWorkshopPosts;
use App\Filament\Org\Resources\WorkshopPosts\Tables\WorkshopPostsTable;
use App\Models\WorkshopPost;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WorkshopPostResource extends Resource
{
    protected static ?string $model = WorkshopPost::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationLabel = '工作坊问答管理';

    protected static ?string $modelLabel = '问答帖';

    protected static ?int $navigationSort = 11;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return WorkshopPostsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWorkshopPosts::route('/'),
        ];
    }
}
