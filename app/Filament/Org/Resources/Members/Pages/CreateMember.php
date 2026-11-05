<?php

namespace App\Filament\Org\Resources\Members\Pages;

use App\Filament\Org\Resources\Members\MemberResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMember extends CreateRecord
{
    protected static string $resource = MemberResource::class;
}
