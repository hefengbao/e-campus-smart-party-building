<?php

namespace App\Filament\Branch\Resources\DevelopmentCases\Pages;

use App\Filament\Branch\Resources\DevelopmentCases\DevelopmentCaseResource;
use App\Models\DevelopmentCase;
use App\Models\DevelopmentStepDefinition;
use App\Models\Member;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateDevelopmentCase extends CreateRecord
{
    protected static string $resource = DevelopmentCaseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['org_id'] = Member::query()->findOrFail($data['member_id'])->org_id;
        $data['status'] = '进行中';
        $data['stage_no'] = 1;
        $data['step_no'] = 1;

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var DevelopmentCase $case */
        $case = $this->getRecord();

        $definitions = DevelopmentStepDefinition::query()
            ->orderBy('step_no')
            ->get();

        foreach ($definitions as $definition) {
            $case->steps()->create([
                'stage_no' => $definition->stage_no,
                'step_no' => $definition->step_no,
                'step_name' => $definition->step_name,
                'status' => '未开始',
                'operator' => Auth::id(),
            ]);
        }
    }
}
