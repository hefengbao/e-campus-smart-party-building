<?php

namespace App\Filament\College\Resources\Transfers\Tables;

use App\Models\Transfer;
use Filament\Actions\Action;
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
                    ->label('转接党员')
                    ->searchable(),
                TextColumn::make('transfer_type')
                    ->label('转接类型')
                    ->badge()
                    ->color('info'),
                TextColumn::make('to_org_name')
                    ->label('去向组织')
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
                        '待院级审核', '待组织部审核' => 'info',
                        '已办结' => 'success',
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
                TextColumn::make('letter_no')
                    ->label('介绍信编号')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('状态')
                    ->options(Transfer::STATUSES),
                SelectFilter::make('transfer_type')
                    ->label('转接类型')
                    ->options(Transfer::TRANSFER_TYPES),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('通过')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('院级审核通过后转交组织部终审。')
                    ->visible(fn (Transfer $record): bool => $record->status === '待院级审核')
                    ->action(function (Transfer $record): void {
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
                    ->visible(fn (Transfer $record): bool => $record->status === '待院级审核')
                    ->action(function (array $data, Transfer $record): void {
                        $record->review(approve: false, stage: 'college', reviewerId: (int) Auth::id(), note: $data['note']);
                    }),
            ])
            ->defaultSort('apply_at', 'desc');
    }
}
