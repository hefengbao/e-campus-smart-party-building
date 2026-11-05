<?php

namespace App\Filament\Member\Resources\AbroadApplications\Pages;

use App\Filament\Member\Resources\AbroadApplications\AbroadApplicationResource;
use App\Models\AbroadApplication;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateAbroadApplication extends CreateRecord
{
    protected static string $resource = AbroadApplicationResource::class;

    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $member = Auth::user()?->member;

        if ($member === null) {
            Notification::make()
                ->danger()
                ->title('当前账号未关联党员档案，无法发起出国（境）申请。')
                ->persistent()
                ->send();

            $this->halt();
        }

        $resolved = AbroadApplication::resolveRoute($data['start_date'], $data['end_date']);

        $data['member_id'] = $member->id;
        $data['duration_days'] = $resolved['duration_days'];
        $data['approval_route'] = $resolved['approval_route'];
        $data['status'] = '待支部审核';

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
