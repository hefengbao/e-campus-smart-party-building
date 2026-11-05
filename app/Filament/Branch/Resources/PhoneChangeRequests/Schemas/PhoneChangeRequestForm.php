<?php

namespace App\Filament\Branch\Resources\PhoneChangeRequests\Schemas;

use App\Models\Member;
use App\Support\DataScope;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PhoneChangeRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('变更申请')
                    ->columns(2)
                    ->schema([
                        Select::make('member_id')
                            ->label('党员')
                            ->options(fn (): array => Member::query()
                                ->when(
                                    $orgIds = DataScope::visibleOrgIds(Auth::user()),
                                    fn (Builder $query) => $query->whereIn('org_id', $orgIds),
                                )
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (callable $set, ?string $state): void {
                                if ($state !== null) {
                                    $set('old_phone', Member::query()->find($state)?->phone);
                                }
                            }),
                        TextInput::make('old_phone')
                            ->label('原手机号')
                            ->tel()
                            ->disabled()
                            ->dehydrated(),
                        TextInput::make('new_phone')
                            ->label('新手机号')
                            ->tel()
                            ->required()
                            ->maxLength(30),
                        TextInput::make('reason')
                            ->label('变更原因')
                            ->maxLength(200)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
