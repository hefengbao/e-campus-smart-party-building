<?php

namespace App\Filament\Member\Resources\WorkshopPosts;

use App\Filament\Member\Resources\WorkshopPosts\Pages\CreateWorkshopPost;
use App\Filament\Member\Resources\WorkshopPosts\Pages\EditWorkshopPost;
use App\Filament\Member\Resources\WorkshopPosts\Pages\ListWorkshopPosts;
use App\Filament\Member\Resources\WorkshopPosts\Schemas\WorkshopPostForm;
use App\Filament\Member\Resources\WorkshopPosts\Tables\WorkshopPostsTable;
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

    protected static ?string $navigationLabel = '工作坊问答';

    protected static ?string $modelLabel = '问答帖';

    protected static ?int $navigationSort = 7;

    public static function form(Schema $schema): Schema
    {
        return WorkshopPostForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WorkshopPostsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWorkshopPosts::route('/'),
            'create' => CreateWorkshopPost::route('/create'),
            'edit' => EditWorkshopPost::route('/{record}/edit'),
        ];
    }
}
