<?php

namespace App\Filament\Branch\Resources\TrainingHours\Pages;

use App\Filament\Branch\Resources\TrainingHours\TrainingHourResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTrainingHours extends ListRecords
{
    protected static string $resource = TrainingHourResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('提交学时'),
        ];
    }
}
