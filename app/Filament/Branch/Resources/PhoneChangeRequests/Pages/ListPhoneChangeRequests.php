<?php

namespace App\Filament\Branch\Resources\PhoneChangeRequests\Pages;

use App\Filament\Branch\Resources\PhoneChangeRequests\PhoneChangeRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPhoneChangeRequests extends ListRecords
{
    protected static string $resource = PhoneChangeRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
