<?php

namespace App\Filament\Member\Resources\TrainingHours\Pages;

use App\Filament\Member\Resources\TrainingHours\TrainingHourResource;
use Filament\Resources\Pages\ListRecords;

class ListTrainingHours extends ListRecords
{
    protected static string $resource = TrainingHourResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
