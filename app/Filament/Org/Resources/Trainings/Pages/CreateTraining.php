<?php

namespace App\Filament\Org\Resources\Trainings\Pages;

use App\Filament\Org\Resources\Trainings\TrainingResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateTraining extends CreateRecord
{
    protected static string $resource = TrainingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();

        return $data;
    }
}
