<?php

namespace App\Filament\Org\Resources\Organizations;

use App\Filament\Org\Resources\Organizations\Pages\CreateOrganization;
use App\Filament\Org\Resources\Organizations\Pages\EditOrganization;
use App\Filament\Org\Resources\Organizations\Pages\ListOrganizations;
use App\Filament\Org\Resources\Organizations\Schemas\OrganizationForm;
use App\Filament\Org\Resources\Organizations\Tables\OrganizationsTable;
use App\Models\Organization;
use App\Support\DataScope;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class OrganizationResource extends Resource
{
    protected static ?string $model = Organization::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $recordTitleAttribute = 'short_name';

    protected static ?string $navigationLabel = '组织管理';

    protected static ?string $modelLabel = '组织';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return OrganizationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrganizationsTable::configure($table);
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
            'index' => ListOrganizations::route('/'),
            'create' => CreateOrganization::route('/create'),
            'edit' => EditOrganization::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return DataScope::applyToOrganizations(
            parent::getEloquentQuery(),
            Auth::user(),
        );
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
