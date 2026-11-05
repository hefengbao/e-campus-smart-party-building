<?php

namespace App\Filament\Branch\Resources\Meetings;

use App\Filament\Branch\Resources\Meetings\Pages\CreateMeeting;
use App\Filament\Branch\Resources\Meetings\Pages\EditMeeting;
use App\Filament\Branch\Resources\Meetings\Pages\ListMeetings;
use App\Filament\Branch\Resources\Meetings\RelationManagers\AttachmentsRelationManager;
use App\Filament\Branch\Resources\Meetings\RelationManagers\AttendeesRelationManager;
use App\Filament\Branch\Resources\Meetings\Schemas\MeetingForm;
use App\Filament\Branch\Resources\Meetings\Tables\MeetingsTable;
use App\Models\Meeting;
use App\Support\DataScope;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class MeetingResource extends Resource
{
    protected static ?string $model = Meeting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationLabel = '三会一课台账';

    protected static ?string $modelLabel = '会议台账';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return MeetingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MeetingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            AttendeesRelationManager::class,
            AttachmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMeetings::route('/'),
            'create' => CreateMeeting::route('/create'),
            'edit' => EditMeeting::route('/{record}/edit'),
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
