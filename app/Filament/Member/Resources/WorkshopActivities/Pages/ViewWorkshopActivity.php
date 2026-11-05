<?php

namespace App\Filament\Member\Resources\WorkshopActivities\Pages;

use App\Filament\Member\Resources\WorkshopActivities\WorkshopActivityResource;
use Filament\Resources\Pages\ViewRecord;

class ViewWorkshopActivity extends ViewRecord
{
    protected static string $resource = WorkshopActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
