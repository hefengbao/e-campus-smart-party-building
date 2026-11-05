<?php

namespace App\Filament\Member\Resources\WorkshopPosts\Pages;

use App\Filament\Member\Resources\WorkshopPosts\WorkshopPostResource;
use App\Support\SensitiveWords;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateWorkshopPost extends CreateRecord
{
    protected static string $resource = WorkshopPostResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $word = SensitiveWords::hit($data['title'] ?? '', $data['content'] ?? '');

        if ($word !== null) {
            Notification::make()
                ->danger()
                ->title("内容包含敏感词「{$word}」，请修改后重新提交。")
                ->persistent()
                ->send();

            $this->halt();
        }

        $data['author'] = (string) Auth::id();
        $data['author_name'] = Auth::user()?->name;

        return $data;
    }
}
