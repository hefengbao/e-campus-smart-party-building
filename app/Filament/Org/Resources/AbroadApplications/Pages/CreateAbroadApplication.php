<?php

namespace App\Filament\Org\Resources\AbroadApplications\Pages;

use App\Filament\Org\Resources\AbroadApplications\AbroadApplicationResource;
use App\Models\AbroadApplication;
use Filament\Resources\Pages\CreateRecord;

class CreateAbroadApplication extends CreateRecord
{
    protected static string $resource = AbroadApplicationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = '待支部审核';

        if (isset($data['start_date'], $data['end_date'])) {
            $resolved = AbroadApplication::resolveRoute($data['start_date'], $data['end_date']);
            $data['duration_days'] = $resolved['duration_days'];
            $data['approval_route'] = $resolved['approval_route'];
        }

        return $data;
    }
}
