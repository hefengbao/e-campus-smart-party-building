<?php

namespace App\Filament\Branch\Resources\AbroadApplications\Tables;

use App\Models\AbroadApplication;
use App\Models\AbroadContactLog;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
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
                    ->label('申请党员')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('destination')
                    ->label('前往国家/地区')
                    ->searchable(),
                TextColumn::make('start_date')
                    ->label('开始日期')
                    ->date('Y-m-d')
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label('结束日期')
                    ->date('Y-m-d')
                    ->sortable(),
                TextColumn::make('duration_days')
                    ->label('天数')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('approval_route')
                    ->label('审批路线')
                    ->badge()
                    ->color(fn (string $state): string => $state === '6个月及以上' ? 'danger' : 'info'),
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
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('状态')
                    ->options(AbroadApplication::STATUSES),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('修改')
                    ->visible(fn (AbroadApplication $record): bool => $record->status === '待支部审核'),
                Action::make('branch-review')
                    ->label('支部初审')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('初审通过后将按审批路线流转。')
                    ->visible(fn (AbroadApplication $record): bool => $record->status === '待支部审核')
                    ->action(function (AbroadApplication $record): void {
                        $record->review(approve: true, stage: 'branch', reviewerId: (int) Auth::id());
                    })
                    ->successNotificationTitle('初审通过，已流转'),
                Action::make('reject')
                    ->label('驳回')
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->visible(fn (AbroadApplication $record): bool => $record->status === '待支部审核')
                    ->schema([
                        Textarea::make('note')
                            ->label('驳回原因')
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->action(function (array $data, AbroadApplication $record): void {
                        $record->review(approve: false, stage: 'branch', reviewerId: (int) Auth::id(), note: $data['note']);
                    })
                    ->successNotificationTitle('已驳回'),
                Action::make('cancel')
                    ->label('销假')
                    ->icon('heroicon-m-arrow-uturn-left')
                    ->color('gray')
                    ->visible(fn (AbroadApplication $record): bool => $record->status === '已批准')
                    ->schema([
                        DatePicker::make('return_date')
                            ->label('实际返回日期')
                            ->default(now())
                            ->required(),
                        Textarea::make('note')
                            ->label('销假备注')
                            ->columnSpanFull(),
                    ])
                    ->action(function (array $data, AbroadApplication $record): void {
                        $record->cancel($data['return_date'], $data['note'] ?? null, (int) Auth::id());
                    })
                    ->successNotificationTitle('已销假'),
                Action::make('contact-log')
                    ->label('联系记录')
                    ->icon('heroicon-m-phone')
                    ->color('info')
                    ->visible(fn (AbroadApplication $record): bool => $record->status === '已批准')
                    ->schema([
                        DatePicker::make('contact_date')
                            ->label('联系日期')
                            ->default(now())
                            ->required(),
                        Select::make('method')
                            ->label('联系方式')
                            ->options(AbroadContactLog::METHODS)
                            ->default('电话')
                            ->required(),
                        Textarea::make('content')
                            ->label('联系内容')
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->action(function (array $data, AbroadApplication $record): void {
                        $record->contactLogs()->create([
                            'contact_date' => $data['contact_date'],
                            'method' => $data['method'],
                            'content' => $data['content'],
                            'operator' => (string) Auth::id(),
                        ]);
                    })
                    ->successNotificationTitle('联系记录已登记'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
