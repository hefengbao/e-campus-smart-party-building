<?php

namespace App\Filament\Org\Resources\AbroadApplications\Tables;

use App\Models\AbroadApplication;
use App\Models\AbroadContactLog;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class AbroadApplicationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('member.name')
                    ->label('姓名')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('destination')
                    ->label('目的地')
                    ->searchable(),
                TextColumn::make('start_date')
                    ->label('出发')
                    ->date('Y-m-d')
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label('返回')
                    ->date('Y-m-d')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('duration_days')
                    ->label('天数')
                    ->alignCenter()
                    ->sortable(),
                TextColumn::make('approval_route')
                    ->label('审批路径')
                    ->badge()
                    ->color(fn (string $state): string => $state === '6个月及以上' ? 'danger' : 'info'),
                TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '待支部审核' => 'warning',
                        '待院级审核' => 'info',
                        '待组织部审核' => 'primary',
                        '已批准' => 'success',
                        '已驳回' => 'danger',
                        '已销假' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('return_date')
                    ->label('实际回国')
                    ->date('Y-m-d')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('状态')
                    ->options(AbroadApplication::STATUSES),
                SelectFilter::make('approval_route')
                    ->label('审批路径')
                    ->options(AbroadApplication::APPROVAL_ROUTES),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (AbroadApplication $record): bool => $record->status === '待支部审核'),
                Action::make('final-review')
                    ->label('终审')
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->visible(fn (AbroadApplication $record): bool => $record->status === '待组织部审核')
                    ->action(function (AbroadApplication $record): void {
                        $record->review(approve: true, stage: 'final', reviewerId: (int) Auth::id());
                    })
                    ->successNotificationTitle('已批准'),
                Action::make('contact-log')
                    ->label('联系记录')
                    ->icon('heroicon-m-phone')
                    ->color('gray')
                    ->visible(fn (AbroadApplication $record): bool => $record->status === '已批准')
                    ->schema([
                        DatePicker::make('contact_date')
                            ->label('联系日期')
                            ->default(now())
                            ->required(),
                        Select::make('method')
                            ->label('方式')
                            ->options(AbroadContactLog::METHODS)
                            ->default('电话')
                            ->required(),
                        Textarea::make('content')
                            ->label('联系内容')
                            ->columnSpanFull(),
                    ])
                    ->action(function (array $data, AbroadApplication $record): void {
                        $record->contactLogs()->create([
                            ...$data,
                            'operator' => Auth::id(),
                        ]);
                    })
                    ->successNotificationTitle('联系记录已登记'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
