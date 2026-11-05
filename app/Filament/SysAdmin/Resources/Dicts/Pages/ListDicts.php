<?php

namespace App\Filament\SysAdmin\Resources\Dicts\Pages;

use App\Filament\SysAdmin\Resources\Dicts\DictResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDicts extends ListRecords
{
    protected static string $resource = DictResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
