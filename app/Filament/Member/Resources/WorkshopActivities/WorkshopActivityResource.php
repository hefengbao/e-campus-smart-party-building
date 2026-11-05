<?php

namespace App\Filament\Member\Resources\WorkshopActivities;

use App\Filament\Member\Resources\WorkshopActivities\Pages\ListWorkshopActivities;
use App\Filament\Member\Resources\WorkshopActivities\Pages\ViewWorkshopActivity;
use App\Filament\Member\Resources\WorkshopActivities\Tables\WorkshopActivitiesTable;
use App\Models\WorkshopActivity;
use BackedEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WorkshopActivityResource extends Resource
{
    protected static ?string $model = WorkshopActivity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationLabel = '党务工作坊活动';

    protected static ?string $modelLabel = '工作坊活动';

    protected static ?int $navigationSort = 8;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('活动详情')
                ->columns(2)
                ->schema([
                    TextInput::make('title')->label('活动主题')->disabled()->columnSpanFull(),
                    TextInput::make('category')->label('活动分类')->disabled(),
                    TextInput::make('organization.name')->label('主办党组织')->disabled(),
                    DateTimePicker::make('start_at')->label('开始时间')->disabled(),
                    DateTimePicker::make('end_at')->label('结束时间')->disabled(),
                    TextInput::make('location')->label('活动地点')->disabled()->columnSpanFull(),
                    Textarea::make('description')->label('活动内容说明')->rows(4)->disabled()->columnSpanFull(),
                    Textarea::make('summary')->label('活动总结与纪要')->rows(3)->disabled()->columnSpanFull()->visible(fn ($record): bool => filled($record?->summary)),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return WorkshopActivitiesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWorkshopActivities::route('/'),
            'view' => ViewWorkshopActivity::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        // 个人端仅展示已发布且公开的活动
        return parent::getEloquentQuery()
            ->where('status', '已发布')
            ->where('is_public', true);
    }
}
