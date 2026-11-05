<?php

namespace App\Filament\Branch\Resources\DevelopmentCases\Pages;

use App\Filament\Branch\Resources\DevelopmentCases\DevelopmentCaseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDevelopmentCases extends ListRecords
{
    protected static string $resource = DevelopmentCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
