<?php

namespace App\Filament\Member\Resources\WorkshopPosts\Pages;

use App\Filament\Member\Resources\WorkshopPosts\WorkshopPostResource;
use Filament\Resources\Pages\ListRecords;

class ListWorkshopPosts extends ListRecords
{
    protected static string $resource = WorkshopPostResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
