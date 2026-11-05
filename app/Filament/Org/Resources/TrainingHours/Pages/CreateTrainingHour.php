<?php

namespace App\Filament\Org\Resources\TrainingHours\Pages;

use App\Filament\Org\Resources\TrainingHours\TrainingHourResource;
use App\Models\Trainee;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateTrainingHour extends CreateRecord
{
    protected static string $resource = TrainingHourResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // 组织部直录 → 直接生效；同步学员归属组织与党员关联。
        $data['status'] = '已生效';
        $data['created_by'] = Auth::id();

        $trainee = Trainee::find($data['trainee_id'] ?? null);
        if ($trainee !== null) {
            $data['org_id'] = $trainee->org_id;
            $data['member_id'] = $trainee->member_id;
        }

        return $data;
    }
}
