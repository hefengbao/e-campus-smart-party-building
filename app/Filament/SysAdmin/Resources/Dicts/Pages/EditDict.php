<?php

namespace App\Filament\SysAdmin\Resources\Dicts\Pages;

use App\Filament\SysAdmin\Resources\Dicts\DictResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDict extends EditRecord
{
    protected static string $resource = DictResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
