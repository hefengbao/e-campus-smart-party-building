<?php

namespace App\Filament\Branch\Resources\AbroadApplications\Pages;

use App\Filament\Branch\Resources\AbroadApplications\AbroadApplicationResource;
use App\Models\AbroadApplication;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAbroadApplication extends EditRecord
{
    protected static string $resource = AbroadApplicationResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        $record = $parameters['record'] ?? null;

        return $record instanceof AbroadApplication && $record->status === '待支部审核';
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
