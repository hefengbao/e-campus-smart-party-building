<?php

namespace App\Filament\Branch\Resources\BirthdayPushes\Pages;

use App\Filament\Branch\Resources\BirthdayPushes\BirthdayPushResource;
use Filament\Resources\Pages\ListRecords;

class ListBirthdayPushes extends ListRecords
{
    protected static string $resource = BirthdayPushResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
