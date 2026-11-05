<?php

namespace App\Filament\College\Resources\AbroadApplications\Pages;

use App\Filament\College\Resources\AbroadApplications\AbroadApplicationResource;
use Filament\Resources\Pages\ListRecords;

class ListAbroadApplications extends ListRecords
{
    protected static string $resource = AbroadApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
