<?php

namespace App\Filament\Branch\Resources\Transfers\Pages;

use App\Filament\Branch\Resources\Transfers\TransferResource;
use App\Models\Transfer;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTransfer extends EditRecord
{
    protected static string $resource = TransferResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        $record = $parameters['record'] ?? null;

        return $record instanceof Transfer && $record->status === '待支部审核';
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
