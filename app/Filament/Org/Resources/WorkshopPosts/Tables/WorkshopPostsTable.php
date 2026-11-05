<?php

namespace App\Filament\Org\Resources\WorkshopPosts\Tables;

use App\Models\WorkshopPost;
use App\Models\WorkshopReply;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class WorkshopPostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('标题')
                    ->searchable()
                    ->limit(30),
                TextColumn::make('tag')
                    ->label('标签')
                    ->badge()
                    ->color('info'),
                TextColumn::make('author_name')
                    ->label('发帖人'),
                TextColumn::make('replies_count')
                    ->counts('replies')
                    ->label('回复数'),
                IconColumn::make('is_pinned')
                    ->label('置顶')
                    ->boolean(),
                IconColumn::make('is_hidden')
                    ->label('隐藏')
                    ->boolean(),
                IconColumn::make('is_resolved')
                    ->label('已解决')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('发帖时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('tag')
                    ->label('标签')
                    ->options(WorkshopPost::TAGS),
            ])
            ->recordActions([
                // 置顶/取消置顶。
                Action::make('toggle_pinned')
                    ->label(fn (WorkshopPost $record): string => $record->is_pinned ? '取消置顶' : '置顶')
                    ->icon('heroicon-m-flag')
                    ->color(fn (WorkshopPost $record): string => $record->is_pinned ? 'gray' : 'warning')
                    ->action(function (WorkshopPost $record): void {
                        $record->forceFill(['is_pinned' => ! $record->is_pinned])->save();
                    }),
                // 隐藏/取消隐藏。
                Action::make('toggle_hidden')
                    ->label(fn (WorkshopPost $record): string => $record->is_hidden ? '取消隐藏' : '隐藏')
                    ->icon('heroicon-m-eye-slash')
                    ->color(fn (WorkshopPost $record): string => $record->is_hidden ? 'gray' : 'danger')
                    ->requiresConfirmation()
                    ->action(function (WorkshopPost $record): void {
                        $record->forceFill(['is_hidden' => ! $record->is_hidden])->save();
                    }),
                // 官方回复：组织部回帖自动标记官方回复。
                Action::make('official_reply')
                    ->label('官方回复')
                    ->icon('heroicon-m-shield-check')
                    ->color('success')
                    ->schema([
                        Textarea::make('content')
                            ->label('回复内容')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->action(function (array $data, WorkshopPost $record): void {
                        WorkshopReply::query()->create([
                            'post_id' => $record->id,
                            'content' => $data['content'],
                            'author' => (string) Auth::id(),
                            'author_name' => Auth::user()?->name,
                            'is_official' => true,
                        ]);
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
