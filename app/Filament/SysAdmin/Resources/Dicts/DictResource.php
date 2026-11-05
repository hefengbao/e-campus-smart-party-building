<?php

namespace App\Filament\SysAdmin\Resources\Dicts;

use App\Filament\SysAdmin\Resources\Dicts\Pages\CreateDict;
use App\Filament\SysAdmin\Resources\Dicts\Pages\EditDict;
use App\Filament\SysAdmin\Resources\Dicts\Pages\ListDicts;
use App\Filament\SysAdmin\Resources\Dicts\Schemas\DictForm;
use App\Filament\SysAdmin\Resources\Dicts\Tables\DictsTable;
use App\Models\Dict;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DictResource extends Resource
{
    protected static ?string $model = Dict::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static ?string $recordTitleAttribute = 'label';

    protected static ?string $navigationLabel = '数据字典';

    protected static ?string $modelLabel = '字典项';

    protected static ?int $navigationSort = 12;

    public static function form(Schema $schema): Schema
    {
        return DictForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DictsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDicts::route('/'),
            'create' => CreateDict::route('/create'),
            'edit' => EditDict::route('/{record}/edit'),
        ];
    }
}
