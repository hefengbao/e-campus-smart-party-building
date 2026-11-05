<?php

namespace App\Filament\Branch\Resources\TrainingEnrollments\Pages;

use App\Filament\Branch\Resources\TrainingEnrollments\TrainingEnrollmentResource;
use Filament\Resources\Pages\ListRecords;

class ListTrainingEnrollments extends ListRecords
{
    protected static string $resource = TrainingEnrollmentResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
