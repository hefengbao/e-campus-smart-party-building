<?php

namespace App\Filament\Branch\Resources\TrainingHours;

use App\Filament\Branch\Resources\TrainingHours\Pages\CreateTrainingHour;
use App\Filament\Branch\Resources\TrainingHours\Pages\EditTrainingHour;
use App\Filament\Branch\Resources\TrainingHours\Pages\ListTrainingHours;
use App\Filament\Branch\Resources\TrainingHours\Schemas\TrainingHourForm;
use App\Filament\Branch\Resources\TrainingHours\Tables\TrainingHoursTable;
use App\Models\TrainingHour;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TrainingHourResource extends Resource
{
    protected static ?string $model = TrainingHour::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationLabel = '学时提交';

    protected static ?string $modelLabel = '学时记录';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return TrainingHourForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TrainingHoursTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        // 协同端只能看到本人提交的学时记录。
        return parent::getEloquentQuery()
            ->where('submitted_by', (string) Auth::id());
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTrainingHours::route('/'),
            'create' => CreateTrainingHour::route('/create'),
            'edit' => EditTrainingHour::route('/{record}/edit'),
        ];
    }
}
