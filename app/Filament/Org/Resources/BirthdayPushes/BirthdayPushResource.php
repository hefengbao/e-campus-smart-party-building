<?php

namespace App\Filament\Org\Resources\BirthdayPushes;

use App\Filament\Org\Resources\BirthdayPushes\Pages\ListBirthdayPushes;
use App\Filament\Org\Resources\BirthdayPushes\Tables\BirthdayPushesTable;
use App\Models\BirthdayPush;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BirthdayPushResource extends Resource
{
    protected static ?string $model = BirthdayPush::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCake;

    protected static ?string $recordTitleAttribute = 'content';

    protected static ?string $navigationLabel = '政治生日台账';

    protected static ?string $modelLabel = '生日推送';

    protected static ?int $navigationSort = 14;

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
        return BirthdayPushesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBirthdayPushes::route('/'),
        ];
    }
}
