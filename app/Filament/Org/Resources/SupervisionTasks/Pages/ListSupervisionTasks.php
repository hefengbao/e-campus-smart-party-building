<?php

namespace App\Filament\Org\Resources\SupervisionTasks\Pages;

use App\Filament\Org\Resources\SupervisionTasks\SupervisionTaskResource;
use Filament\Resources\Pages\ListRecords;

class ListSupervisionTasks extends ListRecords
{
    protected static string $resource = SupervisionTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
