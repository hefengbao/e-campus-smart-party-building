<?php

namespace App\Filament\Branch\Resources\DevelopmentCases;

use App\Filament\Branch\Resources\DevelopmentCases\Pages\CreateDevelopmentCase;
use App\Filament\Branch\Resources\DevelopmentCases\Pages\EditDevelopmentCase;
use App\Filament\Branch\Resources\DevelopmentCases\Pages\ListDevelopmentCases;
use App\Filament\Branch\Resources\DevelopmentCases\RelationManagers\StepsRelationManager;
use App\Filament\Branch\Resources\DevelopmentCases\Schemas\DevelopmentCaseForm;
use App\Filament\Branch\Resources\DevelopmentCases\Tables\DevelopmentCasesTable;
use App\Models\DevelopmentCase;
use App\Support\DataScope;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class DevelopmentCaseResource extends Resource
{
    protected static ?string $model = DevelopmentCase::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?string $recordTitleAttribute = 'member_id';

    protected static ?string $navigationLabel = '党员发展';

    protected static ?string $modelLabel = '发展案件';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return DevelopmentCaseForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DevelopmentCasesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            StepsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDevelopmentCases::route('/'),
            'create' => CreateDevelopmentCase::route('/create'),
            'edit' => EditDevelopmentCase::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return DataScope::apply(
            parent::getEloquentQuery(),
            Auth::user(),
            'org_id',
        );
    }
}
