<?php

namespace App\Filament\SysAdmin\Resources\AuditLogs\Tables;

use Filament\Actions\ViewAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AuditLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('操作时间')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('操作人')
                    ->searchable(),
                TextColumn::make('user_id')
                    ->label('账号 (ID)')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('action')
                    ->label('操作类型')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'login' => 'info',
                        'create' => 'success',
                        'update' => 'warning',
                        'delete' => 'danger',
                        'approve' => 'success',
                        'reject' => 'danger',
                        default => 'gray',
                    })
                    ->searchable(),
                TextColumn::make('subject_type')
                    ->label('目标模型')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '—')
                    ->toggleable(),
                TextColumn::make('subject_id')
                    ->label('目标 ID')
                    ->toggleable(),
                TextColumn::make('ip')
                    ->label('IP 地址')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->label('操作类型')
                    ->options([
                        'login' => '登录',
                        'create' => '新建',
                        'update' => '更新',
                        'delete' => '删除',
                        'approve' => '审核通过',
                        'reject' => '审核驳回',
                        'sync' => '数据同步',
                    ]),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('查看快照')
                    ->schema([
                        TextInput::make('action')->label('操作类型'),
                        TextInput::make('ip')->label('IP 地址'),
                        Textarea::make('user_agent')->label('User Agent')->rows(2)->columnSpanFull(),
                        KeyValue::make('properties')->label('变更快照')->columnSpanFull(),
                    ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
