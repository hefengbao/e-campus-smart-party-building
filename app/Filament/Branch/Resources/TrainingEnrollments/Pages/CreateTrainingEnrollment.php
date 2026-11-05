<?php

namespace App\Filament\Branch\Resources\TrainingEnrollments\Pages;

use App\Filament\Branch\Resources\TrainingEnrollments\TrainingEnrollmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTrainingEnrollment extends CreateRecord
{
    protected static string $resource = TrainingEnrollmentResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return false;
    }
}
