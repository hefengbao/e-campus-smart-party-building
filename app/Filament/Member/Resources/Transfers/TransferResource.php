<?php

namespace App\Filament\Member\Resources\Transfers;

use App\Filament\Member\Resources\Transfers\Pages\CreateTransfer;
use App\Filament\Member\Resources\Transfers\Pages\ListTransfers;
use App\Filament\Member\Resources\Transfers\Tables\TransfersTable;
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

    protected static ?string $navigationLabel = '组织关系转接申请';

    protected static ?string $modelLabel = '转接申请';

    protected static ?int $navigationSort = 4;

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
            'create' => CreateTransfer::route('/create'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('member_id', Auth::user()?->member?->id);
    }
}
