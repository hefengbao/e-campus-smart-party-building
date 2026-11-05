<?php

namespace App\Filament\Org\Resources\Trainings\Pages;

use App\Filament\Org\Resources\Trainings\TrainingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTrainings extends ListRecords
{
    protected static string $resource = TrainingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
