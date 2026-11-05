<?php

namespace App\Filament\Branch\Resources\TrainingHours\Pages;

use App\Filament\Branch\Resources\TrainingHours\TrainingHourResource;
use App\Models\Trainee;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateTrainingHour extends CreateRecord
{
    protected static string $resource = TrainingHourResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $trainee = Trainee::query()
            ->where('staff_no', $data['staff_no'])
            ->where('enabled', true)
            ->first();

        if ($trainee === null) {
            Notification::make()
                ->danger()
                ->title('未找到该工号对应的在训学员，请确认工号是否正确。')
                ->persistent()
                ->send();

            $this->halt();
        }

        $data['trainee_id'] = $trainee->id;
        $data['member_id'] = $trainee->member_id;
        $data['org_id'] = $trainee->org_id;
        $data['year'] = (int) substr((string) $data['occurred_on'], 0, 4);
        $data['status'] = '待审核';
        $data['submitter_org_id'] = Auth::user()?->scopedOrgIds()[0] ?? null;
        $data['submitted_by'] = (string) Auth::id();

        unset($data['staff_no']);

        return $data;
    }
}
