<?php

namespace App\Filament\Org\Resources\AbroadApplications\Schemas;

use App\Models\AbroadApplication;
use App\Models\Member;
use App\Support\DataScope;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AbroadApplicationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('出国（境）信息')
                    ->columns(2)
                    ->schema([
                        Select::make('member_id')
                            ->label('申请党员')
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
                        TextInput::make('destination')
                            ->label('目的地')
                            ->required()
                            ->maxLength(128),
                        DatePicker::make('start_date')
                            ->label('出发日期')
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (callable $get, callable $set) => static::syncDuration($get, $set)),
                        DatePicker::make('end_date')
                            ->label('返回日期')
                            ->required()
                            ->afterOrEqual('start_date')
                            ->live()
                            ->afterStateUpdated(fn (callable $get, callable $set) => static::syncDuration($get, $set)),
                        TextInput::make('duration_days')
                            ->label('天数（自动计算）')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(),
                        TextInput::make('approval_route')
                            ->label('审批路径（自动判定）')
                            ->disabled()
                            ->dehydrated(),
                        Toggle::make('retain_relation')
                            ->label('保留组织关系')
                            ->default(true)
                            ->required(),
                        TextInput::make('contact_phone')
                            ->label('境外联系方式')
                            ->tel()
                            ->maxLength(32),
                        Textarea::make('reason')
                            ->label('事由')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * 按出发/返回日期同步天数与审批路径。
     */
    protected static function syncDuration(callable $get, callable $set): void
    {
        $start = $get('start_date');
        $end = $get('end_date');

        if ($start === null || $end === null) {
            return;
        }

        $resolved = AbroadApplication::resolveRoute($start, $end);
        $set('duration_days', $resolved['duration_days']);
        $set('approval_route', $resolved['approval_route']);
    }
}
