<?php

namespace App\Filament\Branch\Resources\BirthdayPushes\Tables;

use App\Models\BirthdayPush;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BirthdayPushesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('member.name')
                    ->label('党员')
                    ->searchable(),
                TextColumn::make('member.organization.name')
                    ->label('所在组织')
                    ->limit(20),
                TextColumn::make('birthday_date')
                    ->label('政治生日')
                    ->date('Y-m-d')
                    ->sortable(),
                TextColumn::make('years')
                    ->label('第 N 年'),
                TextColumn::make('channel')
                    ->label('渠道')
                    ->badge()
                    ->color('info'),
                TextColumn::make('content')
                    ->label('推送内容')
                    ->limit(30)
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '待推送' => 'warning',
                        '已推送' => 'success',
                        '失败' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('pushed_at')
                    ->label('推送时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('状态')
                    ->options(BirthdayPush::STATUSES),
                SelectFilter::make('channel')
                    ->label('渠道')
                    ->options(BirthdayPush::CHANNELS),
            ])
            ->headerActions([
                // 名单导出（CSV）。
                Action::make('export')
                    ->label('导出名单')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('success')
                    ->action(fn (): StreamedResponse => self::exportCsv()),
            ])
            ->defaultSort('birthday_date', 'desc');
    }

    /**
     * 导出当前过滤范围的政治生日名单（CSV，UTF-8 BOM 兼容 Excel）。
     */
    public static function exportCsv(): StreamedResponse
    {
        $filename = '政治生日名单-'.now()->format('YmdHis').'.csv';

        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['党员', '所在组织', '政治生日', '第 N 年', '渠道', '推送内容', '状态', '推送时间']);

            BirthdayPush::query()
                ->with(['member.organization'])
                ->whereHas('member', fn ($query) => $query->visibleTo(auth()->user()->scopedOrgIds()))
                ->orderBy('birthday_date', 'desc')
                ->chunk(500, function ($rows) use ($out): void {
                    foreach ($rows as $row) {
                        fputcsv($out, [
                            $row->member?->name ?? '—',
                            $row->member?->organization?->name ?? '—',
                            $row->birthday_date?->format('Y-m-d'),
                            $row->years,
                            $row->channel,
                            $row->content,
                            $row->status,
                            $row->pushed_at?->format('Y-m-d H:i:s'),
                        ]);
                    }
                });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
