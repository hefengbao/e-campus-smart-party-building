<?php

namespace App\Filament\Member\Resources\AbroadApplications\Pages;

use App\Filament\Member\Resources\AbroadApplications\AbroadApplicationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAbroadApplications extends ListRecords
{
    protected static string $resource = AbroadApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('发起出国（境）申请'),
        ];
    }
}
