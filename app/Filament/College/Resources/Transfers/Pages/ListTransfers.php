<?php

namespace App\Filament\College\Resources\Transfers\Pages;

use App\Filament\College\Resources\Transfers\TransferResource;
use Filament\Resources\Pages\ListRecords;

class ListTransfers extends ListRecords
{
    protected static string $resource = TransferResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
