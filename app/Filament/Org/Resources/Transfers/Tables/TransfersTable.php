<?php

namespace App\Filament\Org\Resources\Transfers\Tables;

use App\Models\Transfer;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
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
                    ->label('姓名')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('direction')
                    ->label('方向')
                    ->badge()
                    ->color(fn (string $state): string => $state === '转出' ? 'warning' : 'success'),
                TextColumn::make('transfer_type')
                    ->label('类型')
                    ->toggleable(),
                TextColumn::make('fromOrganization.short_name')
                    ->label('转出组织')
                    ->toggleable(),
                TextColumn::make('to_org_name')
                    ->label('去向组织')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '待支部审核' => 'warning',
                        '待院级审核' => 'info',
                        '待组织部审核' => 'primary',
                        '已办结' => 'success',
                        '已驳回' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('letter_no')
                    ->label('介绍信编号')
                    ->placeholder('—')
                    ->copyable(),
                TextColumn::make('apply_at')
                    ->label('申请时间')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('finished_at')
                    ->label('办结时间')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('状态')
                    ->options(Transfer::STATUSES),
                SelectFilter::make('direction')
                    ->label('方向')
                    ->options(Transfer::DIRECTIONS),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (Transfer $record): bool => $record->status === '待支部审核'),
                Action::make('final-review')
                    ->label('终审')
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->visible(fn (Transfer $record): bool => $record->status === '待组织部审核')
                    ->schema([
                        Textarea::make('note')
                            ->label('审批意见')
                            ->columnSpanFull(),
                    ])
                    ->action(function (array $data, Transfer $record): void {
                        $record->review(approve: true, stage: 'final', reviewerId: (int) Auth::id(), note: $data['note'] ?? null);
                    })
                    ->successNotificationTitle('已办结，介绍信编号已生成'),
                Action::make('reject')
                    ->label('驳回')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->visible(fn (Transfer $record): bool => ! in_array($record->status, ['已办结', '已驳回'], true))
                    ->schema([
                        Textarea::make('note')
                            ->label('驳回原因')
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->action(function (array $data, Transfer $record): void {
                        $record->review(approve: false, stage: 'final', reviewerId: (int) Auth::id(), note: $data['note']);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
