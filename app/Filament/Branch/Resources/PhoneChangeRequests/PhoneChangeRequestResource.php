<?php

namespace App\Filament\Branch\Resources\PhoneChangeRequests;

use App\Filament\Branch\Resources\PhoneChangeRequests\Pages\CreatePhoneChangeRequest;
use App\Filament\Branch\Resources\PhoneChangeRequests\Pages\ListPhoneChangeRequests;
use App\Filament\Branch\Resources\PhoneChangeRequests\Schemas\PhoneChangeRequestForm;
use App\Filament\Branch\Resources\PhoneChangeRequests\Tables\PhoneChangeRequestsTable;
use App\Models\PhoneChangeRequest;
use App\Support\DataScope;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PhoneChangeRequestResource extends Resource
{
    protected static ?string $model = PhoneChangeRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDevicePhoneMobile;

    protected static ?string $recordTitleAttribute = 'new_phone';

    protected static ?string $navigationLabel = '手机号变更审核';

    protected static ?string $modelLabel = '手机号变更';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return PhoneChangeRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PhoneChangeRequestsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPhoneChangeRequests::route('/'),
            'create' => CreatePhoneChangeRequest::route('/create'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return DataScope::apply(
            parent::getEloquentQuery(),
            Auth::user(),
            'member.org_id',
        );
    }
}
