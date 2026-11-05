<?php

namespace App\Filament\SysAdmin\Resources\Configs\Pages;

use App\Filament\SysAdmin\Resources\Configs\ConfigResource;
use Filament\Resources\Pages\CreateRecord;

class CreateConfig extends CreateRecord
{
    protected static string $resource = ConfigResource::class;
}
