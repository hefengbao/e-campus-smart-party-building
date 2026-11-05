<?php

namespace App\Filament\Org\Resources\WorkshopLinks\Pages;

use App\Filament\Org\Resources\WorkshopLinks\WorkshopLinkResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateWorkshopLink extends CreateRecord
{
    protected static string $resource = WorkshopLinkResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = (string) Auth::id();

        return $data;
    }
}
