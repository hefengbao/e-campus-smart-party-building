<?php

namespace App\Filament\Org\Resources\FeeRecords\Pages;

use App\Filament\Org\Resources\FeeRecords\FeeRecordResource;
use Filament\Resources\Pages\ListRecords;

class ListFeeRecords extends ListRecords
{
    protected static string $resource = FeeRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
