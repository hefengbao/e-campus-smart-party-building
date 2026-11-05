<?php

namespace App\Filament\Branch\Resources\Meetings\RelationManagers;

use App\Models\MeetingAttachment;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class AttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'attachments';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('kind')
                    ->label('材料类型')
                    ->options(MeetingAttachment::KINDS)
                    ->native(false)
                    ->default('纪要')
                    ->required(),
                TextInput::make('name')
                    ->label('材料名称')
                    ->required()
                    ->maxLength(255),
                FileUpload::make('file_path')
                    ->label('文件')
                    ->disk('public')
                    ->directory('meetings')
                    ->maxSize(20480)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('kind')
                    ->label('材料类型')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '纪要' => 'success',
                        '影像' => 'info',
                        '签到表' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('name')
                    ->label('材料名称')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('上传时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(fn (array $data): array => $data + ['uploaded_by' => Auth::id()]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
