<?php

namespace App\Filament\Member\Resources\WorkshopPosts\Tables;

use App\Models\WorkshopPost;
use App\Models\WorkshopReply;
use App\Support\SensitiveWords;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
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
                // 回帖：敏感词过滤。
                Action::make('reply')
                    ->label('回帖')
                    ->icon('heroicon-m-chat-bubble-left')
                    ->color('info')
                    ->schema([
                        Textarea::make('content')
                            ->label('回复内容')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->action(function (array $data, WorkshopPost $record): void {
                        $word = SensitiveWords::hit($data['content']);

                        if ($word !== null) {
                            Notification::make()
                                ->danger()
                                ->title("内容包含敏感词「{$word}」，请修改后重新提交。")
                                ->send();

                            return;
                        }

                        WorkshopReply::query()->create([
                            'post_id' => $record->id,
                            'content' => $data['content'],
                            'author' => (string) Auth::id(),
                            'author_name' => Auth::user()?->name,
                        ]);
                    }),
                // 楼主标记解决。
                Action::make('mark_resolved')
                    ->label('标记解决')
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->visible(fn (WorkshopPost $record): bool => $record->author === (string) Auth::id()
                        && ! $record->is_resolved)
                    ->action(function (WorkshopPost $record): void {
                        $record->forceFill(['is_resolved' => true])->save();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
