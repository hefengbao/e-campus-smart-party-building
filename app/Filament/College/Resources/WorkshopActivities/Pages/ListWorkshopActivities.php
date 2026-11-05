<?php

namespace App\Filament\College\Resources\WorkshopActivities\Pages;

use App\Filament\College\Resources\WorkshopActivities\WorkshopActivityResource;
use Filament\Resources\Pages\ListRecords;

class ListWorkshopActivities extends ListRecords
{
    protected static string $resource = WorkshopActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
