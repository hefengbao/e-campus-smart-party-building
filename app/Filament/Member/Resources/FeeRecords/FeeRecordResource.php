<?php

namespace App\Filament\Member\Resources\FeeRecords;

use App\Filament\Member\Resources\FeeRecords\Pages\ListFeeRecords;
use App\Filament\Member\Resources\FeeRecords\Tables\FeeRecordsTable;
use App\Models\FeeRecord;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class FeeRecordResource extends Resource
{
    protected static ?string $model = FeeRecord::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $recordTitleAttribute = 'period';

    protected static ?string $navigationLabel = '我的党费记录';

    protected static ?string $modelLabel = '党费记录';

    protected static ?int $navigationSort = 2;

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
        return FeeRecordsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFeeRecords::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $memberId = Auth::user()?->member?->id ?? Auth::user()?->member_id;

        if ($memberId === null) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        return parent::getEloquentQuery()->where('member_id', $memberId);
    }
}
