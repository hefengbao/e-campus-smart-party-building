<?php

namespace App\Filament\Org\Resources\WorkshopLinks;

use App\Filament\Org\Resources\WorkshopLinks\Pages\CreateWorkshopLink;
use App\Filament\Org\Resources\WorkshopLinks\Pages\EditWorkshopLink;
use App\Filament\Org\Resources\WorkshopLinks\Pages\ListWorkshopLinks;
use App\Filament\Org\Resources\WorkshopLinks\Schemas\WorkshopLinkForm;
use App\Filament\Org\Resources\WorkshopLinks\Tables\WorkshopLinksTable;
use App\Models\WorkshopLink;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WorkshopLinkResource extends Resource
{
    protected static ?string $model = WorkshopLink::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationLabel = '工作坊链接';

    protected static ?string $modelLabel = '工作坊链接';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return WorkshopLinkForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WorkshopLinksTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWorkshopLinks::route('/'),
            'create' => CreateWorkshopLink::route('/create'),
            'edit' => EditWorkshopLink::route('/{record}/edit'),
        ];
    }
}
