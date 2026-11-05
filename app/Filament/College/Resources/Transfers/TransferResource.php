<?php

namespace App\Filament\College\Resources\Transfers;

use App\Filament\College\Resources\Transfers\Pages\ListTransfers;
use App\Filament\College\Resources\Transfers\Tables\TransfersTable;
use App\Models\Transfer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TransferResource extends Resource
{
    protected static ?string $model = Transfer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static ?string $navigationLabel = '组织关系转接审核';

    protected static ?string $modelLabel = '组织关系转接';

    protected static ?int $navigationSort = 4;

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
        return TransfersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTransfers::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $orgIds = Auth::user()?->scopedOrgIds() ?? [];

        return parent::getEloquentQuery()
            ->when(
                $orgIds !== [],
                fn (Builder $query) => $query->whereIn('from_org_id', $orgIds),
            );
    }
}
