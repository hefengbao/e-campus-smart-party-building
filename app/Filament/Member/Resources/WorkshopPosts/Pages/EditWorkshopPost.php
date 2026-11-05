<?php

namespace App\Filament\Member\Resources\WorkshopPosts\Pages;

use App\Filament\Member\Resources\WorkshopPosts\WorkshopPostResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditWorkshopPost extends EditRecord
{
    protected static string $resource = WorkshopPostResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        // 仅楼主可编辑自己的帖子。
        return parent::canAccess($parameters)
            && $parameters['record'] instanceof Model
            && $parameters['record']->author === (string) Auth::id();
    }
}
