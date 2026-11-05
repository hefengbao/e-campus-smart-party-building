<?php

namespace App\Filament\SysAdmin\Resources\Configs;

use App\Filament\SysAdmin\Resources\Configs\Pages\CreateConfig;
use App\Filament\SysAdmin\Resources\Configs\Pages\EditConfig;
use App\Filament\SysAdmin\Resources\Configs\Pages\ListConfigs;
use App\Filament\SysAdmin\Resources\Configs\Schemas\ConfigForm;
use App\Filament\SysAdmin\Resources\Configs\Tables\ConfigsTable;
use App\Models\Config;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ConfigResource extends Resource
{
    protected static ?string $model = Config::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = '系统参数';

    protected static ?string $modelLabel = '参数';

    protected static ?int $navigationSort = 11;

    public static function form(Schema $schema): Schema
    {
        return ConfigForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ConfigsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConfigs::route('/'),
            'create' => CreateConfig::route('/create'),
            'edit' => EditConfig::route('/{record}/edit'),
        ];
    }
}
