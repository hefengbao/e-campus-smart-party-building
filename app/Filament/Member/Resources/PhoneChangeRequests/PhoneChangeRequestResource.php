<?php

namespace App\Filament\Member\Resources\PhoneChangeRequests;

use App\Filament\Member\Resources\PhoneChangeRequests\Pages\CreatePhoneChangeRequest;
use App\Filament\Member\Resources\PhoneChangeRequests\Pages\ListPhoneChangeRequests;
use App\Filament\Member\Resources\PhoneChangeRequests\Schemas\PhoneChangeRequestForm;
use App\Filament\Member\Resources\PhoneChangeRequests\Tables\PhoneChangeRequestsTable;
use App\Models\PhoneChangeRequest;
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

    protected static ?string $navigationLabel = '手机号变更申请';

    protected static ?string $modelLabel = '手机号变更申请';

    protected static ?int $navigationSort = 6;

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
        $memberId = Auth::user()?->member?->id ?? Auth::user()?->member_id;

        if ($memberId === null) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        return parent::getEloquentQuery()->where('member_id', $memberId);
    }
}
