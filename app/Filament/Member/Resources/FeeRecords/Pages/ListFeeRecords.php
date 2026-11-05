<?php

namespace App\Filament\Member\Resources\FeeRecords\Pages;

use App\Filament\Member\Resources\FeeRecords\FeeRecordResource;
use Filament\Resources\Pages\ListRecords;

class ListFeeRecords extends ListRecords
{
    protected static string $resource = FeeRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
