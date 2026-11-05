<?php

namespace App\Filament\Org\Resources\Organizations\Schemas;

use App\Models\Organization;
use App\Models\User;
use App\Support\DataScope;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class OrganizationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('组织编码')
                    ->required()
                    ->maxLength(30)
                    ->unique(ignoreRecord: true)
                    ->disabled(fn (): bool => ! self::currentUser()?->can('org:update-code'))
                    ->dehydrated()
                    ->hintIcon('heroicon-m-information-circle', tooltip: '组织编码仅党委组织部可修改'),
                TextInput::make('short_name')
                    ->label('组织简称')
                    ->required()
                    ->maxLength(50),
                TextInput::make('full_name')
                    ->label('组织全称')
                    ->required()
                    ->maxLength(120),
                Select::make('level')
                    ->label('组织层级')
                    ->options([
                        1 => '学校党委',
                        2 => '二级党委',
                        3 => '党支部',
                    ])
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set): void {
                        if ((int) $get('level') !== 3) {
                            $set('member_type', null);
                            $set('nature', null);
                        }
                    }),
                Select::make('parent_id')
                    ->label('上级组织')
                    ->relationship(
                        name: 'parent',
                        titleAttribute: 'short_name',
                        modifyQueryUsing: fn (Builder $query) => DataScope::applyToOrganizations(
                            $query,
                            Auth::user(),
                        ),
                        ignoreRecord: true,
                    )
                    ->searchable()
                    ->preload()
                    ->getOptionLabelFromRecordUsing(fn (Organization $record): string => sprintf(
                        '%s（%s）',
                        $record->short_name,
                        [1 => '学校党委', 2 => '二级党委', 3 => '党支部'][$record->level] ?? '未知',
                    ))
                    ->hidden(fn (Get $get): bool => (int) $get('level') === 1),
                Select::make('member_type')
                    ->label('成员类型')
                    ->options([
                        'tea' => '教工',
                        'stu' => '学生',
                        'mix' => '混合',
                    ])
                    ->visible(fn (Get $get): bool => (int) $get('level') === 3)
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set): void {
                        $set('nature', Organization::natureFromMemberType($get('member_type')));
                    }),
                Select::make('status')
                    ->label('状态')
                    ->options([
                        '正常' => '正常',
                        '撤销' => '撤销',
                        '合并' => '合并',
                    ])
                    ->required()
                    ->default('正常'),
                TextInput::make('secretary')
                    ->label('书记')
                    ->maxLength(30),
                TextInput::make('contact_person')
                    ->label('联系人')
                    ->maxLength(30),
                TextInput::make('contact_phone')
                    ->label('联系电话')
                    ->tel()
                    ->maxLength(30),
                DatePicker::make('founded_date')
                    ->label('成立日期'),
                DatePicker::make('term_start_date')
                    ->label('本届任期开始'),
                TextInput::make('term_years')
                    ->label('任期（年）')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(10),
                DatePicker::make('term_end_date')
                    ->label('本届任期结束'),
                TextInput::make('category')
                    ->label('组织类别')
                    ->maxLength(30),
                TextInput::make('sort_order')
                    ->label('排序')
                    ->required()
                    ->numeric()
                    ->default(0),
                Textarea::make('remark')
                    ->label('备注')
                    ->columnSpanFull(),
            ]);
    }

    private static function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }
}
