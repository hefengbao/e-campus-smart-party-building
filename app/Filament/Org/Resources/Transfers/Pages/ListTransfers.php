<?php

namespace App\Filament\Org\Resources\Transfers\Pages;

use App\Filament\Org\Resources\Transfers\TransferResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTransfers extends ListRecords
{
    protected static string $resource = TransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
