<?php

namespace App\Filament\Member\Resources\DevelopmentCases;

use App\Filament\Member\Resources\DevelopmentCases\Pages\ListDevelopmentCases;
use App\Filament\Member\Resources\DevelopmentCases\Pages\ViewDevelopmentCase;
use App\Filament\Member\Resources\DevelopmentCases\RelationManagers\StepsRelationManager;
use App\Filament\Member\Resources\DevelopmentCases\Tables\DevelopmentCasesTable;
use App\Models\DevelopmentCase;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class DevelopmentCaseResource extends Resource
{
    protected static ?string $model = DevelopmentCase::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?string $recordTitleAttribute = 'member_id';

    protected static ?string $navigationLabel = '入党发展纪实';

    protected static ?string $modelLabel = '入党发展案';

    protected static ?int $navigationSort = 7;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('发展档案概览')
                ->columns(3)
                ->schema([
                    TextInput::make('stage_text')
                        ->label('当前阶段')
                        ->formatStateUsing(fn (DevelopmentCase $record): string => '第 '.$record->stage_no.' 阶段：'.(DevelopmentCase::STAGES[$record->stage_no][1] ?? ''))
                        ->disabled(),
                    TextInput::make('step_text')
                        ->label('当前步骤')
                        ->formatStateUsing(fn (DevelopmentCase $record): string => '第 '.$record->step_no.' 步：'.($record->currentStep()?->step_name ?? '—'))
                        ->disabled(),
                    TextInput::make('status')
                        ->label('案件状态')
                        ->disabled(),
                    DatePicker::make('apply_date')
                        ->label('申请入党日期')
                        ->disabled(),
                    DatePicker::make('started_at')
                        ->label('建档时间')
                        ->disabled(),
                    TextInput::make('organization.name')
                        ->label('培养党组织')
                        ->disabled(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return DevelopmentCasesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            StepsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDevelopmentCases::route('/'),
            'view' => ViewDevelopmentCase::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $memberId = $user?->member?->id ?? $user?->member_id;
        $userId = $user?->id;

        return parent::getEloquentQuery()->where(function (Builder $query) use ($memberId, $userId): void {
            if ($memberId !== null) {
                $query->where('member_id', $memberId);
            }

            if ($userId !== null && $userId !== $memberId) {
                $query->orWhere('member_id', $userId);
            }

            if ($memberId === null && $userId === null) {
                $query->whereRaw('1 = 0');
            }
        });
    }
}
