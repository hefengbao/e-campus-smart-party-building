<?php

namespace App\Filament\Org\Resources\TrainingHours\Pages;

use App\Filament\Org\Resources\TrainingHours\TrainingHourResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTrainingHour extends EditRecord
{
    protected static string $resource = TrainingHourResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
