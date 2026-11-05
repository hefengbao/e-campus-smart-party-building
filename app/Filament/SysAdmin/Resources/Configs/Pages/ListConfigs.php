<?php

namespace App\Filament\SysAdmin\Resources\Configs\Pages;

use App\Filament\SysAdmin\Resources\Configs\ConfigResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListConfigs extends ListRecords
{
    protected static string $resource = ConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
