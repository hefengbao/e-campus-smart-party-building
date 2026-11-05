<?php

namespace App\Filament\Branch\Resources\Meetings\Pages;

use App\Filament\Branch\Resources\Meetings\MeetingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMeeting extends EditRecord
{
    protected static string $resource = MeetingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
