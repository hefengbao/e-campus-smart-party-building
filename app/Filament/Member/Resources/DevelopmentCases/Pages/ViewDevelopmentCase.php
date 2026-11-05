<?php

namespace App\Filament\Member\Resources\DevelopmentCases\Pages;

use App\Filament\Member\Resources\DevelopmentCases\DevelopmentCaseResource;
use Filament\Resources\Pages\ViewRecord;

class ViewDevelopmentCase extends ViewRecord
{
    protected static string $resource = DevelopmentCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
