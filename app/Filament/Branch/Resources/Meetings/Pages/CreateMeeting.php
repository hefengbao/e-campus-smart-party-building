<?php

namespace App\Filament\Branch\Resources\Meetings\Pages;

use App\Filament\Branch\Resources\Meetings\MeetingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMeeting extends CreateRecord
{
    protected static string $resource = MeetingResource::class;
}
