<?php

namespace App\Filament\Branch\Resources\Transfers\Tables;

use App\Models\Transfer;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class TransfersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('member.name')
                    ->label('转出党员')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('transfer_type')
                    ->label('转接类型')
                    ->badge()
                    ->color('info'),
                TextColumn::make('to_org_name')
                    ->label('转入组织')
                    ->searchable()
                    ->limit(30),
                TextColumn::make('apply_at')
                    ->label('申请时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '待支部审核' => 'warning',
                        '待院级审核' => 'info',
                        '待组织部审核' => 'info',
                        '已办结' => 'success',
                        '已驳回' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('branch_reviewed_at')
                    ->label('支部审核时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('letter_no')
                    ->label('介绍信编号')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('状态')
                    ->options(Transfer::STATUSES),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('修改')
                    ->visible(fn (Transfer $record): bool => $record->status === '待支部审核'),
                Action::make('branch-review')
                    ->label('支部初审')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('初审通过后将流转至院级党组织审核。')
                    ->visible(fn (Transfer $record): bool => $record->status === '待支部审核')
                    ->action(function (Transfer $record): void {
                        $record->review(approve: true, stage: 'branch', reviewerId: (int) Auth::id());
                    })
                    ->successNotificationTitle('初审通过，待院级审核'),
                Action::make('reject')
                    ->label('驳回')
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->visible(fn (Transfer $record): bool => $record->status === '待支部审核')
                    ->schema([
                        Textarea::make('note')
                            ->label('驳回原因')
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->action(function (array $data, Transfer $record): void {
                        $record->review(approve: false, stage: 'branch', reviewerId: (int) Auth::id(), note: $data['note']);
                    })
                    ->successNotificationTitle('已驳回'),
            ])
            ->defaultSort('apply_at', 'desc');
    }
}
