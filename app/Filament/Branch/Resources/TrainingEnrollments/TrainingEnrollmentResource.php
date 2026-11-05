<?php

namespace App\Filament\Branch\Resources\TrainingEnrollments;

use App\Filament\Branch\Resources\TrainingEnrollments\Pages\ListTrainingEnrollments;
use App\Filament\Branch\Resources\TrainingEnrollments\Tables\TrainingEnrollmentsTable;
use App\Models\TrainingEnrollment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TrainingEnrollmentResource extends Resource
{
    protected static ?string $model = TrainingEnrollment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $navigationLabel = '培训报名审核';

    protected static ?string $modelLabel = '培训报名';

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
        return TrainingEnrollmentsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $orgIds = Auth::user()?->scopedOrgIds() ?? [];

        return parent::getEloquentQuery()->visibleTo($orgIds);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTrainingEnrollments::route('/'),
        ];
    }
}
