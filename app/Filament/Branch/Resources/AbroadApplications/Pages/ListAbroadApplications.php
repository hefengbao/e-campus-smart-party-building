<?php

namespace App\Filament\Branch\Resources\AbroadApplications\Pages;

use App\Filament\Branch\Resources\AbroadApplications\AbroadApplicationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAbroadApplications extends ListRecords
{
    protected static string $resource = AbroadApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('代录出国（境）申请'),
        ];
    }
}
