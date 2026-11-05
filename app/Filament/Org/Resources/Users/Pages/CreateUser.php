<?php

namespace App\Filament\Org\Resources\Users\Pages;

use App\Filament\Org\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
