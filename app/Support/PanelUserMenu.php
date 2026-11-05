<?php

namespace App\Support;

use Filament\Actions\Action;
use Filament\Facades\Filament;

class PanelUserMenu
{
    /**
     * User-menu profile header for every panel.
     *
     * @return array<string, Action>
     */
    public static function items(): array
    {
        return [
            'profile' => Action::make('profile')
                ->label(fn (): string => Filament::auth()->user()?->name ?? '个人信息')
                ->sort(-1),
        ];
    }
}
