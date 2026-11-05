<?php

namespace App\Filament\Org\Resources\AbroadApplications\Pages;

use App\Filament\Org\Resources\AbroadApplications\AbroadApplicationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAbroadApplications extends ListRecords
{
    protected static string $resource = AbroadApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
