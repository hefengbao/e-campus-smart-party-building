<?php

namespace App\Filament\Branch\Resources\AbroadApplications\Pages;

use App\Filament\Branch\Resources\AbroadApplications\AbroadApplicationResource;
use App\Models\AbroadApplication;
use Filament\Resources\Pages\CreateRecord;

class CreateAbroadApplication extends CreateRecord
{
    protected static string $resource = AbroadApplicationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $route = AbroadApplication::resolveRoute(
            (string) $data['start_date'],
            (string) $data['end_date'],
        );

        $data['duration_days'] = $route['duration_days'];
        $data['approval_route'] = $route['approval_route'];
        $data['status'] = '待支部审核';

        return $data;
    }
}
