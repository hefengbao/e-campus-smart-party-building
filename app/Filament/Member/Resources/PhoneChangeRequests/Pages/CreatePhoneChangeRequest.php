<?php

namespace App\Filament\Member\Resources\PhoneChangeRequests\Pages;

use App\Filament\Member\Resources\PhoneChangeRequests\PhoneChangeRequestResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreatePhoneChangeRequest extends CreateRecord
{
    protected static string $resource = PhoneChangeRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();
        $data['member_id'] = $user?->member?->id ?? $user?->member_id;
        $data['old_phone'] = $user?->member?->phone ?? $user?->phone;
        $data['status'] = '待审核';

        return $data;
    }
}
