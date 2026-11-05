<?php

namespace App\Filament\SysAdmin\Resources\Users\Pages;

use App\Filament\SysAdmin\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
