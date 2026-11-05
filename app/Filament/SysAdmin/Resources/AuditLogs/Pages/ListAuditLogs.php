<?php

namespace App\Filament\SysAdmin\Resources\AuditLogs\Pages;

use App\Filament\SysAdmin\Resources\AuditLogs\AuditLogResource;
use Filament\Resources\Pages\ListRecords;

class ListAuditLogs extends ListRecords
{
    protected static string $resource = AuditLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
