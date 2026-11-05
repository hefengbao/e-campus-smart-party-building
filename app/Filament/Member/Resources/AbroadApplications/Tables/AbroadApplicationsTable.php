<?php

namespace App\Filament\Member\Resources\AbroadApplications\Tables;

use App\Models\AbroadApplication;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AbroadApplicationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('destination')
                    ->label('目的地')
                    ->searchable(),
                TextColumn::make('start_date')
                    ->label('出发日期')
                    ->date('Y-m-d')
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label('返回日期')
                    ->date('Y-m-d')
                    ->sortable(),
                TextColumn::make('duration_days')
                    ->label('天数')
                    ->numeric()
                    ->alignCenter(),
                TextColumn::make('approval_route')
                    ->label('审批路径')
                    ->badge()
                    ->color(fn (string $state): string => $state === '6个月及以上' ? 'danger' : 'gray'),
                TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '待支部审核' => 'warning',
                        '待院级审核', '待组织部审核' => 'info',
                        '已批准' => 'success',
                        '已销假' => 'gray',
                        '已驳回' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('return_date')
                    ->label('销假日期')
                    ->date('Y-m-d')
                    ->toggleable(),
                TextColumn::make('remark')
                    ->label('备注')
                    ->limit(30)
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('状态')
                    ->options(AbroadApplication::STATUSES),
            ])
            ->recordActions([
                Action::make('extension-apply')
                    ->label('申请延期')
                    ->icon('heroicon-m-clock')
                    ->color('warning')
                    ->visible(fn (AbroadApplication $record): bool => $record->status === '已批准')
                    ->schema([
                        TextInput::make('extend_months')
                            ->label('延长月数')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                        Textarea::make('reason')
                            ->label('延期原因')
                            ->required()
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->action(function (array $data, AbroadApplication $record): void {
                        $record->extensions()->create([
                            'extend_months' => (int) $data['extend_months'],
                            'reason' => $data['reason'],
                            'status' => '待审批',
                            'applied_at' => now(),
                        ]);
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
