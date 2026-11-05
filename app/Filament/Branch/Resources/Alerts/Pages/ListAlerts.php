<?php

namespace App\Filament\Branch\Resources\Alerts\Pages;

use App\Filament\Branch\Resources\Alerts\AlertResource;
use Filament\Resources\Pages\ListRecords;

class ListAlerts extends ListRecords
{
    protected static string $resource = AlertResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
