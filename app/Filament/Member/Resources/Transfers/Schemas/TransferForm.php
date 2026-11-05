<?php

namespace App\Filament\Member\Resources\Transfers\Schemas;

use App\Models\Organization;
use App\Models\Transfer;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class TransferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('direction')
                    ->label('转接方向')
                    ->options(Transfer::DIRECTIONS)
                    ->default('转出')
                    ->required()
                    ->live(),
                Select::make('transfer_type')
                    ->label('转接类型')
                    ->options(Transfer::TRANSFER_TYPES)
                    ->default('校外')
                    ->required()
                    ->live(),
                Select::make('to_org_id')
                    ->label('目标组织（校内转接）')
                    ->options(fn (): array => Organization::query()
                        ->orderBy('path')
                        ->get()
                        ->mapWithKeys(fn (Organization $org): array => [$org->id => $org->full_name])
                        ->all())
                    ->visible(fn (Get $get): bool => $get('transfer_type') === '校内')
                    ->required(fn (Get $get): bool => $get('transfer_type') === '校内')
                    ->searchable(),
                TextInput::make('to_org_name')
                    ->label('去向单位名称')
                    ->maxLength(255)
                    ->visible(fn (Get $get): bool => $get('transfer_type') !== '校内')
                    ->required(fn (Get $get): bool => $get('transfer_type') !== '校内'),
                TextInput::make('to_org_code')
                    ->label('去向单位编码')
                    ->maxLength(64)
                    ->visible(fn (Get $get): bool => $get('transfer_type') !== '校内'),
                Textarea::make('reason')
                    ->label('申请事由')
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }
}
