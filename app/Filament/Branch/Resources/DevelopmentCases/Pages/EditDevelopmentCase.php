<?php

namespace App\Filament\Branch\Resources\DevelopmentCases\Pages;

use App\Filament\Branch\Resources\DevelopmentCases\DevelopmentCaseResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDevelopmentCase extends EditRecord
{
    protected static string $resource = DevelopmentCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
