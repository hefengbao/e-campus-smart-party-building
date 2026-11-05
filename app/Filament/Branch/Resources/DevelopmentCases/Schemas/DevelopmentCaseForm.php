<?php

namespace App\Filament\Branch\Resources\DevelopmentCases\Schemas;

use App\Models\DevelopmentCase;
use App\Models\Member;
use App\Support\DataScope;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class DevelopmentCaseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('发展案件')
                    ->columns(2)
                    ->schema([
                        Select::make('member_id')
                            ->label('发展对象')
                            ->options(fn (?DevelopmentCase $record): array => Member::query()
                                ->when($record === null, fn (Builder $query) => $query->whereDoesntHave('developmentCase'))
                                ->when(
                                    $orgIds = DataScope::visibleOrgIds(Auth::user()),
                                    fn (Builder $query) => $query->whereIn('org_id', $orgIds),
                                )
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->required()
                            ->disabled(fn (?DevelopmentCase $record): bool => $record !== null)
                            ->helperText('仅列出尚未建档的党员'),
                        DatePicker::make('apply_date')
                            ->label('递交入党申请书日期')
                            ->maxDate(now()),
                        Textarea::make('remark')
                            ->label('备注')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
