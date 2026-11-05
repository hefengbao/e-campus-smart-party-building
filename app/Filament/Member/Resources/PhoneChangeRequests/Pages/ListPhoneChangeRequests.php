<?php

namespace App\Filament\Member\Resources\PhoneChangeRequests\Pages;

use App\Filament\Member\Resources\PhoneChangeRequests\PhoneChangeRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPhoneChangeRequests extends ListRecords
{
    protected static string $resource = PhoneChangeRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('申请修改手机号'),
        ];
    }
}
