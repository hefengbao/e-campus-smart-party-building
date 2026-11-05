<?php

namespace App\Filament\Org\Resources\Trainings;

use App\Filament\Org\Resources\Trainings\Pages\CreateTraining;
use App\Filament\Org\Resources\Trainings\Pages\EditTraining;
use App\Filament\Org\Resources\Trainings\Pages\ListTrainings;
use App\Filament\Org\Resources\Trainings\Schemas\TrainingForm;
use App\Filament\Org\Resources\Trainings\Tables\TrainingsTable;
use App\Models\Training;
use App\Support\DataScope;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TrainingResource extends Resource
{
    protected static ?string $model = Training::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationLabel = '培训发布';

    protected static ?string $modelLabel = '培训项目';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return TrainingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TrainingsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return DataScope::apply(
            parent::getEloquentQuery(),
            Auth::user(),
            'org_id',
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
            'index' => ListTrainings::route('/'),
            'create' => CreateTraining::route('/create'),
            'edit' => EditTraining::route('/{record}/edit'),
        ];
    }
}
