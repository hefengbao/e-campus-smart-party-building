<?php

namespace App\Filament\Org\Resources\TrainingHours\Pages;

use App\Filament\Org\Resources\TrainingHours\TrainingHourResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTrainingHours extends ListRecords
{
    protected static string $resource = TrainingHourResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
