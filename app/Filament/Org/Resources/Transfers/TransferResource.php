<?php

namespace App\Filament\Org\Resources\Transfers;

use App\Filament\Org\Resources\Transfers\Pages\CreateTransfer;
use App\Filament\Org\Resources\Transfers\Pages\EditTransfer;
use App\Filament\Org\Resources\Transfers\Pages\ListTransfers;
use App\Filament\Org\Resources\Transfers\Schemas\TransferForm;
use App\Filament\Org\Resources\Transfers\Tables\TransfersTable;
use App\Models\Transfer;
use App\Support\DataScope;
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

    protected static ?string $recordTitleAttribute = 'letter_no';

    protected static ?string $navigationLabel = '组织关系转接';

    protected static ?string $modelLabel = '转接申请';

    protected static ?int $navigationSort = 8;

    public static function form(Schema $schema): Schema
    {
        return TransferForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TransfersTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return DataScope::apply(
            parent::getEloquentQuery(),
            Auth::user(),
            'from_org_id',
        );
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTransfers::route('/'),
            'create' => CreateTransfer::route('/create'),
            'edit' => EditTransfer::route('/{record}/edit'),
        ];
    }
}
