<?php

namespace App\Filament\Org\Resources\Trainees;

use App\Filament\Org\Resources\Trainees\Pages\CreateTrainee;
use App\Filament\Org\Resources\Trainees\Pages\EditTrainee;
use App\Filament\Org\Resources\Trainees\Pages\ListTrainees;
use App\Filament\Org\Resources\Trainees\Schemas\TraineeForm;
use App\Filament\Org\Resources\Trainees\Tables\TraineesTable;
use App\Models\Trainee;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TraineeResource extends Resource
{
    protected static ?string $model = Trainee::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = '学员管理';

    protected static ?string $modelLabel = '培训学员';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return TraineeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TraineesTable::configure($table);
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
            'index' => ListTrainees::route('/'),
            'create' => CreateTrainee::route('/create'),
            'edit' => EditTrainee::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
