<?php

namespace App\Filament\Branch\Resources\PhoneChangeRequests\Pages;

use App\Filament\Branch\Resources\PhoneChangeRequests\PhoneChangeRequestResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePhoneChangeRequest extends CreateRecord
{
    protected static string $resource = PhoneChangeRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = '待审核';

        return $data;
    }
}
