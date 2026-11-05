<?php

namespace App\Filament\College\Resources\WorkshopActivities;

use App\Filament\College\Resources\WorkshopActivities\Pages\CreateWorkshopActivity;
use App\Filament\College\Resources\WorkshopActivities\Pages\EditWorkshopActivity;
use App\Filament\College\Resources\WorkshopActivities\Pages\ListWorkshopActivities;
use App\Filament\College\Resources\WorkshopActivities\Schemas\WorkshopActivityForm;
use App\Filament\College\Resources\WorkshopActivities\Tables\WorkshopActivitiesTable;
use App\Models\WorkshopActivity;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class WorkshopActivityResource extends Resource
{
    protected static ?string $model = WorkshopActivity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationLabel = '工作坊活动';

    protected static ?string $modelLabel = '工作坊活动';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return WorkshopActivityForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WorkshopActivitiesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        // 院级端只管理本单位发布的活动。
        $orgIds = Auth::user()?->scopedOrgIds() ?? [];

        return parent::getEloquentQuery()
            ->when(
                $orgIds !== [],
                fn (Builder $query) => $query->whereIn('org_id', $orgIds),
            );
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWorkshopActivities::route('/'),
            'create' => CreateWorkshopActivity::route('/create'),
            'edit' => EditWorkshopActivity::route('/{record}/edit'),
        ];
    }
}
