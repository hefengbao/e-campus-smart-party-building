<?php

namespace App\Filament\Org\Resources\Users\Tables;

use App\Models\RoleRegistry;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('姓名')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('邮箱')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->label('联系电话')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('roleGrants.role_code')
                    ->label('角色')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => RoleRegistry::label($state))
                    ->color(fn (string $state): string => match ($state) {
                        'super_admin' => 'danger',
                        'org_dept' => 'warning',
                        'college', 'training_coordinator' => 'info',
                        'branch' => 'primary',
                        default => 'gray',
                    }),
                TextColumn::make('member.name')
                    ->label('关联党员')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('更新时间')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('角色')
                    ->options(RoleRegistry::LABELS)
                    ->query(function ($query, array $data): void {
                        if (filled($data['value'] ?? null)) {
                            $query->whereHas('roleGrants', fn ($q) => $q
                                ->where('role_code', $data['value'])
                                ->where('status', '正常'));
                        }
                    }),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }
}
