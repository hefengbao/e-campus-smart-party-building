<?php

namespace App\Filament\Org\Resources\SupervisionTasks;

use App\Filament\Org\Resources\SupervisionTasks\Pages\CreateSupervisionTask;
use App\Filament\Org\Resources\SupervisionTasks\Pages\EditSupervisionTask;
use App\Filament\Org\Resources\SupervisionTasks\Pages\ListSupervisionTasks;
use App\Filament\Org\Resources\SupervisionTasks\Schemas\SupervisionTaskForm;
use App\Filament\Org\Resources\SupervisionTasks\Tables\SupervisionTasksTable;
use App\Models\SupervisionTask;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SupervisionTaskResource extends Resource
{
    protected static ?string $model = SupervisionTask::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationLabel = '党建任务督办';

    protected static ?string $modelLabel = '督办任务';

    protected static ?int $navigationSort = 13;

    public static function form(Schema $schema): Schema
    {
        return SupervisionTaskForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupervisionTasksTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSupervisionTasks::route('/'),
            'create' => CreateSupervisionTask::route('/create'),
            'edit' => EditSupervisionTask::route('/{record}/edit'),
        ];
    }
}
