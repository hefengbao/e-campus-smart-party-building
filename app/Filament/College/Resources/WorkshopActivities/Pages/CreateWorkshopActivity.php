<?php

namespace App\Filament\College\Resources\WorkshopActivities\Pages;

use App\Filament\College\Resources\WorkshopActivities\WorkshopActivityResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateWorkshopActivity extends CreateRecord
{
    protected static string $resource = WorkshopActivityResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['org_id'] = Auth::user()?->scopedOrgIds()[0] ?? null;
        $data['status'] = '草稿';
        $data['created_by'] = (string) Auth::id();

        return $data;
    }
}
