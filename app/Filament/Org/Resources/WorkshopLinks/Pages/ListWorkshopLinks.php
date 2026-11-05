<?php

namespace App\Filament\Org\Resources\WorkshopLinks\Pages;

use App\Filament\Org\Resources\WorkshopLinks\WorkshopLinkResource;
use Filament\Resources\Pages\ListRecords;

class ListWorkshopLinks extends ListRecords
{
    protected static string $resource = WorkshopLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
