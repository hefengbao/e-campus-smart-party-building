<?php

namespace App\Filament\Member\Resources\AbroadApplications;

use App\Filament\Member\Resources\AbroadApplications\Pages\CreateAbroadApplication;
use App\Filament\Member\Resources\AbroadApplications\Pages\ListAbroadApplications;
use App\Filament\Member\Resources\AbroadApplications\Schemas\AbroadApplicationForm;
use App\Filament\Member\Resources\AbroadApplications\Tables\AbroadApplicationsTable;
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

    protected static ?string $navigationLabel = '出国（境）申请';

    protected static ?string $modelLabel = '出国（境）申请';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return AbroadApplicationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AbroadApplicationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAbroadApplications::route('/'),
            'create' => CreateAbroadApplication::route('/create'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('member_id', Auth::user()?->member?->id);
    }
}
