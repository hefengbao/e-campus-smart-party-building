<?php

namespace App\Filament\Branch\Resources\Transfers\Pages;

use App\Filament\Branch\Resources\Transfers\TransferResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTransfers extends ListRecords
{
    protected static string $resource = TransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('代录转接申请'),
        ];
    }
}
