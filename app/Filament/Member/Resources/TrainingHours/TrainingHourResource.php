<?php

namespace App\Filament\Member\Resources\TrainingHours;

use App\Filament\Member\Resources\TrainingHours\Pages\ListTrainingHours;
use App\Filament\Member\Resources\TrainingHours\Tables\TrainingHoursTable;
use App\Models\Trainee;
use App\Models\TrainingHour;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TrainingHourResource extends Resource
{
    protected static ?string $model = TrainingHour::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $navigationLabel = '我的培训档案';

    protected static ?string $modelLabel = '培训学时';

    protected static ?int $navigationSort = 3;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return TrainingHoursTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTrainingHours::route('/'),
        ];
    }

    /**
     * 兼容正式/预备党员（member_id）与党外中层干部（trainee_id）的个人学时流水。
     */
    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $memberId = $user?->member?->id ?? $user?->member_id;
        $traineeId = Trainee::query()->where('staff_no', $user?->id)->value('id');

        return parent::getEloquentQuery()->where(function (Builder $query) use ($memberId, $traineeId): void {
            if ($memberId !== null) {
                $query->where('member_id', $memberId);
            }

            if ($traineeId !== null) {
                $query->orWhere('trainee_id', $traineeId);
            }

            if ($memberId === null && $traineeId === null) {
                $query->whereRaw('1 = 0');
            }
        });
    }
}
