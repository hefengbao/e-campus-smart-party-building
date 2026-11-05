<?php

namespace App\Filament\College\Resources\Meetings;

use App\Filament\College\Resources\Meetings\Pages\ListMeetings;
use App\Filament\College\Resources\Meetings\Pages\ViewMeeting;
use App\Filament\College\Resources\Meetings\Tables\MeetingsTable;
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

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationLabel = '三会一课审核';

    protected static ?string $modelLabel = '会议台账';

    protected static ?int $navigationSort = 2;

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
        return MeetingsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMeetings::route('/'),
            'view' => ViewMeeting::route('/{record}'),
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
