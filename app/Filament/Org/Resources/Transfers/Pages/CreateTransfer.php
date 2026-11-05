<?php

namespace App\Filament\Org\Resources\Transfers\Pages;

use App\Filament\Org\Resources\Transfers\TransferResource;
use App\Models\Member;
use Filament\Resources\Pages\CreateRecord;

class CreateTransfer extends CreateRecord
{
    protected static string $resource = TransferResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = '待支部审核';
        $data['apply_at'] = now();

        $member = Member::find($data['member_id'] ?? null);
        $data['from_org_id'] = $member?->org_id;

        return $data;
    }
}
