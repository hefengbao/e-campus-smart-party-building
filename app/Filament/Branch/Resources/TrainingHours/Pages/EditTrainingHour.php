<?php

namespace App\Filament\Branch\Resources\TrainingHours\Pages;

use App\Filament\Branch\Resources\TrainingHours\TrainingHourResource;
use App\Models\Trainee;
use App\Models\TrainingHour;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditTrainingHour extends EditRecord
{
    protected static string $resource = TrainingHourResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        $record = $parameters['record'] ?? null;

        return $record instanceof TrainingHour
            && $record->status === '待审核'
            && $record->submitted_by === (string) Auth::id();
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['staff_no'] = $this->getRecord()?->trainee?->staff_no;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
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

        unset($data['staff_no']);

        return $data;
    }
}
