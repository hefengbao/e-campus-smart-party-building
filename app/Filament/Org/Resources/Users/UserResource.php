<?php

namespace App\Filament\Org\Resources\Users;

use App\Filament\Org\Resources\Users\Pages\CreateUser;
use App\Filament\Org\Resources\Users\Pages\EditUser;
use App\Filament\Org\Resources\Users\Pages\ListUsers;
use App\Filament\Org\Resources\Users\Schemas\UserForm;
use App\Filament\Org\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = '账号与授权';

    protected static ?string $modelLabel = '账号';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
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
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
