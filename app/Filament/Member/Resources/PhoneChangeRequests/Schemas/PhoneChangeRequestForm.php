<?php

namespace App\Filament\Member\Resources\PhoneChangeRequests\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class PhoneChangeRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('联系电话变更')
                    ->description('提交后将由所在党支部审核，审核通过后自动更新个人档案与账号联系电话。')
                    ->columns(2)
                    ->schema([
                        TextInput::make('old_phone')
                            ->label('原联系电话')
                            ->default(fn (): ?string => Auth::user()?->member?->phone ?? Auth::user()?->phone)
                            ->disabled()
                            ->dehydrated(),
                        TextInput::make('new_phone')
                            ->label('新联系电话')
                            ->tel()
                            ->required()
                            ->maxLength(30),
                        Textarea::make('reason')
                            ->label('变更原因说明')
                            ->rows(3)
                            ->maxLength(200)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
