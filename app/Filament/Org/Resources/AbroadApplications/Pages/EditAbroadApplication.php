<?php

namespace App\Filament\Org\Resources\AbroadApplications\Pages;

use App\Filament\Org\Resources\AbroadApplications\AbroadApplicationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAbroadApplication extends EditRecord
{
    protected static string $resource = AbroadApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
