<?php

namespace App\Filament\College\Resources\AbroadApplications\Tables;

use App\Models\AbroadApplication;
use App\Models\AbroadExtension;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
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
                    ->searchable(),
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
                TextColumn::make('branch_reviewed_at')
                    ->label('支部审核时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('college_reviewed_at')
                    ->label('院级审核时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('return_date')
                    ->label('销假日期')
                    ->date('Y-m-d')
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
                Action::make('approve')
                    ->label('通过')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription(fn (AbroadApplication $record): string => $record->approval_route === '6个月及以上'
                        ? '院级审核通过后转交组织部终审。'
                        : '院级审核为终审，通过后申请生效。')
                    ->visible(fn (AbroadApplication $record): bool => $record->status === '待院级审核')
                    ->action(function (AbroadApplication $record): void {
                        $record->review(approve: true, stage: 'college', reviewerId: (int) Auth::id());
                    }),
                Action::make('reject')
                    ->label('驳回')
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->schema([
                        Textarea::make('note')
                            ->label('驳回原因')
                            ->required()
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (AbroadApplication $record): bool => $record->status === '待院级审核')
                    ->action(function (array $data, AbroadApplication $record): void {
                        $record->review(approve: false, stage: 'college', reviewerId: (int) Auth::id(), note: $data['note']);
                    }),
                Action::make('extension-review')
                    ->label('延期审批')
                    ->icon('heroicon-m-clock')
                    ->color('warning')
                    ->visible(
                        fn (AbroadApplication $record): bool => $record->extensions()
                            ->where('status', '待审批')
                            ->exists(),
                    )
                    ->schema([
                        Select::make('extension_id')
                            ->label('延期申请')
                            ->required()
                            ->options(
                                fn (AbroadApplication $record): array => $record->extensions()
                                    ->where('status', '待审批')
                                    ->pluck('extend_months', 'id')
                                    ->map(fn (int $months): string => "延长 {$months} 个月")
                                    ->all(),
                            ),
                        Toggle::make('approve')
                            ->label('批准')
                            ->default(true)
                            ->required(),
                        Textarea::make('note')
                            ->label('审批意见')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->action(function (array $data, AbroadApplication $record): void {
                        $extension = AbroadExtension::query()->findOrFail($data['extension_id']);

                        $extension->update([
                            'status' => $data['approve'] ? '已批准' : '已驳回',
                            'approved_at' => now(),
                            'approver' => (string) Auth::id(),
                        ]);
                    }),
            ])
            ->defaultSort('applied_at', 'desc');
    }
}
