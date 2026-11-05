<?php

namespace App\Filament\Org\Resources\SupervisionTasks\Pages;

use App\Filament\Org\Resources\SupervisionTasks\SupervisionTaskResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateSupervisionTask extends CreateRecord
{
    protected static string $resource = SupervisionTaskResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = '进行中';
        $data['created_by'] = (string) Auth::id();

        return $data;
    }
}
