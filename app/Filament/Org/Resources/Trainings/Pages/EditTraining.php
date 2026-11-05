<?php

namespace App\Filament\Org\Resources\Trainings\Pages;

use App\Filament\Org\Resources\Trainings\TrainingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTraining extends EditRecord
{
    protected static string $resource = TrainingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
