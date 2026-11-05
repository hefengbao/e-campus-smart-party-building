<?php

namespace App\Filament\Member\Resources\Transfers\Pages;

use App\Filament\Member\Resources\Transfers\TransferResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTransfers extends ListRecords
{
    protected static string $resource = TransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('发起转接申请'),
        ];
    }
}
