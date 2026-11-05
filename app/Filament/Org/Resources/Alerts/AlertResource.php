<?php

namespace App\Filament\Org\Resources\Alerts;

use App\Filament\Org\Resources\Alerts\Pages\ListAlerts;
use App\Filament\Org\Resources\Alerts\Tables\AlertsTable;
use App\Models\Alert;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AlertResource extends Resource
{
    protected static ?string $model = Alert::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationLabel = '监督预警';

    protected static ?string $modelLabel = '预警';

    protected static ?int $navigationSort = 12;

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
        return AlertsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAlerts::route('/'),
        ];
    }
}
