<?php

namespace App\Filament\Org\Resources\WorkshopActivities;

use App\Filament\Org\Resources\WorkshopActivities\Pages\ListWorkshopActivities;
use App\Filament\Org\Resources\WorkshopActivities\Tables\WorkshopActivitiesTable;
use App\Models\WorkshopActivity;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WorkshopActivityResource extends Resource
{
    protected static ?string $model = WorkshopActivity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationLabel = '工作坊活动审核';

    protected static ?string $modelLabel = '工作坊活动';

    protected static ?int $navigationSort = 9;

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
        return WorkshopActivitiesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWorkshopActivities::route('/'),
        ];
    }
}
