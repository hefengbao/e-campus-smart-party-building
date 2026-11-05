<?php

namespace App\Filament\Branch\Resources\Transfers\Pages;

use App\Filament\Branch\Resources\Transfers\TransferResource;
use App\Models\Member;
use Filament\Resources\Pages\CreateRecord;

class CreateTransfer extends CreateRecord
{
    protected static string $resource = TransferResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $member = Member::find($data['member_id'] ?? null);

        $data['direction'] = '转出';
        $data['from_org_id'] = $member?->org_id;
        $data['status'] = '待支部审核';
        $data['apply_at'] = now();

        return $data;
    }
}
