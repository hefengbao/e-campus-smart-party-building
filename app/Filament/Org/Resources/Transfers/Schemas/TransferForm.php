<?php

namespace App\Filament\Org\Resources\Transfers\Schemas;

use App\Models\Member;
use App\Models\Transfer;
use App\Support\DataScope;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TransferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('转接信息')
                    ->columns(2)
                    ->schema([
                        Select::make('member_id')
                            ->label('转接党员')
                            ->options(fn (): array => Member::query()
                                ->when(
                                    $orgIds = DataScope::visibleOrgIds(Auth::user()),
                                    fn (Builder $query) => $query->visibleTo($orgIds),
                                )
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->required(),
                        Select::make('direction')
                            ->label('方向')
                            ->options(Transfer::DIRECTIONS)
                            ->default('转出')
                            ->required(),
                        Select::make('transfer_type')
                            ->label('类型')
                            ->options(Transfer::TRANSFER_TYPES)
                            ->default('校外')
                            ->required(),
                        TextInput::make('to_org_name')
                            ->label('去向组织名称')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('to_org_code')
                            ->label('去向组织编码')
                            ->maxLength(64),
                        DatePicker::make('apply_at')
                            ->label('申请日期')
                            ->default(now())
                            ->required(),
                        Textarea::make('reason')
                            ->label('转接原因')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
