<?php

namespace App\Filament\College\Resources\AbroadApplications;

use App\Filament\College\Resources\AbroadApplications\Pages\ListAbroadApplications;
use App\Filament\College\Resources\AbroadApplications\Tables\AbroadApplicationsTable;
use App\Models\AbroadApplication;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AbroadApplicationResource extends Resource
{
    protected static ?string $model = AbroadApplication::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAmericas;

    protected static ?string $navigationLabel = '出国（境）审核';

    protected static ?string $modelLabel = '出国（境）申请';

    protected static ?int $navigationSort = 5;

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
        return AbroadApplicationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAbroadApplications::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $orgIds = Auth::user()?->scopedOrgIds() ?? [];

        return parent::getEloquentQuery()
            ->when(
                $orgIds !== [],
                fn (Builder $query) => $query->whereHas(
                    'member',
                    fn (Builder $q) => $q->whereIn('org_id', $orgIds),
                ),
            );
    }
}
