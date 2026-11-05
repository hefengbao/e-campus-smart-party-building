<?php

namespace App\Filament\Org\Resources\FeeRecords;

use App\Filament\Org\Resources\FeeRecords\Pages\ListFeeRecords;
use App\Filament\Org\Resources\FeeRecords\Tables\FeeRecordsTable;
use App\Models\FeeRecord;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FeeRecordResource extends Resource
{
    protected static ?string $model = FeeRecord::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $recordTitleAttribute = 'period';

    protected static ?string $navigationLabel = '党费汇总（只读）';

    protected static ?string $modelLabel = '党费记录';

    protected static ?int $navigationSort = 15;

    public static function canCreate(): bool
    {
        // 党费数据只读，仅由 fee/sync 端点同步。
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return FeeRecordsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFeeRecords::route('/'),
        ];
    }
}
