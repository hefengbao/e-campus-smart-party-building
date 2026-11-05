<?php

namespace App\Filament\Org\Resources\WorkshopActivities\Pages;

use App\Filament\Org\Resources\WorkshopActivities\WorkshopActivityResource;
use Filament\Resources\Pages\ListRecords;

class ListWorkshopActivities extends ListRecords
{
    protected static string $resource = WorkshopActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
