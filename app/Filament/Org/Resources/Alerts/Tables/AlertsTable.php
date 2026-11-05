<?php

namespace App\Filament\Org\Resources\Alerts\Tables;

use App\Models\Alert;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class AlertsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('rule')
                    ->label('规则')
                    ->badge()
                    ->color('info')
                    ->searchable(),
                TextColumn::make('level')
                    ->label('级别')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '提示' => 'info',
                        '警告' => 'warning',
                        '严重' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('title')
                    ->label('标题')
                    ->limit(35)
                    ->searchable(),
                TextColumn::make('organization.name')
                    ->label('所属组织')
                    ->limit(20),
                TextColumn::make('member.name')
                    ->label('关联党员')
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '未处置' => 'warning',
                        '已处置' => 'success',
                        '已忽略' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('handled_at')
                    ->label('处置时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('handle_note')
                    ->label('处置说明')
                    ->limit(20)
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('rule')
                    ->label('规则')
                    ->options(Alert::RULES),
                SelectFilter::make('level')
                    ->label('级别')
                    ->options(Alert::LEVELS),
                SelectFilter::make('status')
                    ->label('状态')
                    ->options(Alert::STATUSES),
            ])
            ->recordActions([
                // 处置。
                Action::make('handle')
                    ->label('处置')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->schema([
                        Textarea::make('note')
                            ->label('处置说明')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (Alert $record): bool => $record->status === '未处置')
                    ->action(function (array $data, Alert $record): void {
                        $record->handle('已处置', (int) Auth::id(), $data['note']);
                    }),
                // 忽略。
                Action::make('ignore')
                    ->label('忽略')
                    ->icon('heroicon-m-eye-slash')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription('忽略后该预警不再出现在待处置列表（重复扫描不会复活）。')
                    ->visible(fn (Alert $record): bool => $record->status === '未处置')
                    ->action(function (Alert $record): void {
                        $record->handle('已忽略', (int) Auth::id());
                    }),
            ])
            ->defaultSort('level')
            ->defaultSort('created_at', 'desc');
    }
}
