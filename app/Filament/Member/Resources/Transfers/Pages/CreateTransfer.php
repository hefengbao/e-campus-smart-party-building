<?php

namespace App\Filament\Member\Resources\Transfers\Pages;

use App\Filament\Member\Resources\Transfers\TransferResource;
use App\Models\Organization;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateTransfer extends CreateRecord
{
    protected static string $resource = TransferResource::class;

    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $member = Auth::user()?->member;

        if ($member === null) {
            Notification::make()
                ->danger()
                ->title('当前账号未关联党员档案，无法发起转接申请。')
                ->persistent()
                ->send();

            $this->halt();
        }

        if (($data['transfer_type'] ?? '') === '校内' && ! empty($data['to_org_id'])) {
            $org = Organization::find($data['to_org_id']);
            $data['to_org_name'] = $org?->full_name ?? ($data['to_org_name'] ?? '');
            $data['to_org_code'] = $org?->code;
        }

        unset($data['to_org_id']);

        $data['member_id'] = $member->id;
        $data['from_org_id'] = $member->org_id;
        $data['status'] = '待支部审核';
        $data['apply_at'] = now();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
