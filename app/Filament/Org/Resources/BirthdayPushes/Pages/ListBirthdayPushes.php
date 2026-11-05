<?php

namespace App\Filament\Org\Resources\BirthdayPushes\Pages;

use App\Filament\Org\Resources\BirthdayPushes\BirthdayPushResource;
use Filament\Resources\Pages\ListRecords;

class ListBirthdayPushes extends ListRecords
{
    protected static string $resource = BirthdayPushResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
