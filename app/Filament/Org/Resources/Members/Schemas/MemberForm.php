<?php

namespace App\Filament\Org\Resources\Members\Schemas;

use App\Models\Member;
use App\Models\Organization;
use App\Support\DataScope;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class MemberForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('基本信息')
                    ->columns(3)
                    ->schema([
                        TextInput::make('name')
                            ->label('姓名')
                            ->required()
                            ->maxLength(50),
                        Select::make('gender')
                            ->label('性别')
                            ->options(['男' => '男', '女' => '女'])
                            ->native(false),
                        TextInput::make('ethnicity')
                            ->label('民族')
                            ->maxLength(30)
                            ->default('汉族'),
                        DatePicker::make('birth_date')
                            ->label('出生日期')
                            ->maxDate(now()),
                        TextInput::make('id_card')
                            ->label('身份证号')
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->length(18)
                            ->rule(fn (Get $get, ?Member $record): \Closure => function (string $attribute, $value, \Closure $fail) use ($record): void {
                                if (filled($value) && Member::idCardExists((string) $value, $record?->getKey())) {
                                    $fail('该身份证号已存在。');
                                }
                            })
                            ->live(onBlur: true)
                            ->afterStateHydrated(function (TextInput $component, ?string $state, ?Member $record): void {
                                if ($record?->exists) {
                                    $component->state($record->masked_id_card);
                                }
                            })
                            ->dehydrated(fn (?string $state, ?Member $record): bool => $record === null || ! $record->exists || $state !== $record->masked_id_card)
                            ->hintIcon('heroicon-m-shield-check', tooltip: '加密存储；编辑时显示脱敏值，输入新值才会替换'),
                        TextInput::make('staff_no')
                            ->label('工号/学号')
                            ->required()
                            ->maxLength(30)
                            ->unique(ignoreRecord: true),
                        Select::make('relation_in_school')
                            ->label('在校关系')
                            ->options(['教职工' => '教职工', '学生' => '学生'])
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set): void {
                                if ($get('relation_in_school') === '学生') {
                                    $set('work_unit', null);
                                }
                            }),
                        TextInput::make('phone')
                            ->label('联系电话')
                            ->tel()
                            ->maxLength(30),
                        TextInput::make('work_unit')
                            ->label('工作单位')
                            ->maxLength(100),
                    ]),
                Section::make('组织关系')
                    ->columns(3)
                    ->schema([
                        Select::make('org_id')
                            ->label('所在党支部')
                            ->options(fn (): array => DataScope::applyToOrganizations(
                                Organization::query()->where('level', 3),
                                Auth::user(),
                            )->orderBy('short_name')->pluck('short_name', 'id')->all())
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('parent_org_id')
                            ->label('上级党委')
                            ->relationship(
                                name: 'parentOrganization',
                                titleAttribute: 'short_name',
                                modifyQueryUsing: fn (Builder $query) => DataScope::applyToOrganizations(
                                    $query,
                                    Auth::user(),
                                ),
                            )
                            ->searchable()
                            ->preload(),
                        DatePicker::make('arrive_school_date')
                            ->label('来校日期'),
                        TextInput::make('grade')
                            ->label('年级')
                            ->maxLength(20)
                            ->visible(fn (Get $get): bool => $get('relation_in_school') === '学生'),
                    ]),
                Section::make('党内信息')
                    ->columns(3)
                    ->schema([
                        DatePicker::make('join_party_date')
                            ->label('入党时间')
                            ->rule(function (Get $get) {
                                return function (string $attribute, mixed $value, \Closure $fail) use ($get): void {
                                    $birth = $get('birth_date');

                                    if (filled($birth) && filled($value)) {
                                        $years = Carbon::parse($birth)->diffInYears(Carbon::parse($value));

                                        if ($years < 18 && ! filled($get('special_note'))) {
                                            $fail('入党时间距出生日期不足 18 年，需填写特殊备注说明。');
                                        }
                                    }
                                };
                            }),
                        DatePicker::make('full_member_date')
                            ->label('转正时间')
                            ->rule(function (Get $get) {
                                return function (string $attribute, mixed $value, \Closure $fail) use ($get): void {
                                    $join = $get('join_party_date');

                                    if (filled($join) && filled($value)) {
                                        $years = Carbon::parse($join)->diffInYears(Carbon::parse($value));

                                        if ($years < 1 && ! filled($get('special_note'))) {
                                            $fail('转正时间距入党时间不足 1 年，需填写特殊备注说明。');
                                        }
                                    }
                                };
                            }),
                        Select::make('identity')
                            ->label('身份')
                            ->options([
                                '正式党员' => '正式党员',
                                '预备党员' => '预备党员',
                                '入党积极分子' => '入党积极分子',
                            ])
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set): void {
                                if ($get('identity') === '预备党员') {
                                    $set('full_member_date', null);
                                }
                            }),
                        Select::make('education')
                            ->label('学历')
                            ->options([
                                '博士' => '博士',
                                '硕士' => '硕士',
                                '本科' => '本科',
                                '专科' => '专科',
                                '其他' => '其他',
                            ])
                            ->native(false),
                        Select::make('degree')
                            ->label('学位')
                            ->options([
                                '博士' => '博士',
                                '硕士' => '硕士',
                                '学士' => '学士',
                                '无' => '无',
                            ])
                            ->native(false),
                        TextInput::make('native_place')
                            ->label('籍贯')
                            ->maxLength(100),
                        TextInput::make('source_place')
                            ->label('来源地')
                            ->maxLength(100),
                        TextInput::make('cultivation_level')
                            ->label('培养层次')
                            ->maxLength(30),
                        TextInput::make('professional_title')
                            ->label('职称')
                            ->maxLength(50),
                        TextInput::make('professional_title_level')
                            ->label('职称等级')
                            ->maxLength(30),
                        TextInput::make('member_category')
                            ->label('党员类别')
                            ->maxLength(30),
                        Select::make('status')
                            ->label('状态')
                            ->options([
                                '正常' => '正常',
                                '停止党籍' => '停止党籍',
                                '开除党籍' => '开除党籍',
                                '死亡' => '死亡',
                                '转出' => '转出',
                            ])
                            ->required()
                            ->default('正常')
                            ->native(false),
                        Select::make('data_source')
                            ->label('数据来源')
                            ->options([
                                '初始导入' => '初始导入',
                                '外部同步' => '外部同步',
                                '手工录入' => '手工录入',
                            ])
                            ->required()
                            ->default('手工录入')
                            ->native(false),
                    ]),
                Textarea::make('special_note')
                    ->label('特殊备注')
                    ->columnSpanFull()
                    ->hintIcon('heroicon-m-exclamation-triangle', tooltip: '入党年龄或转正年限校验不通过时必填'),
            ]);
    }
}
