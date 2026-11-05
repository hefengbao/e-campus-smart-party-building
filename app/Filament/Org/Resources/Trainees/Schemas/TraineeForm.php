<?php

namespace App\Filament\Org\Resources\Trainees\Schemas;

use App\Models\Member;
use App\Models\Trainee;
use App\Support\DataScope;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TraineeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('基本信息')
                    ->columns(2)
                    ->schema([
                        TextInput::make('staff_no')
                            ->label('工号')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(64),
                        TextInput::make('name')
                            ->label('姓名')
                            ->required()
                            ->maxLength(64),
                        Select::make('gender')
                            ->label('性别')
                            ->options(['男' => '男', '女' => '女'])
                            ->default('男')
                            ->required(),
                        DatePicker::make('birth_date')
                            ->label('出生日期')
                            ->maxDate(now()),
                        TextInput::make('phone')
                            ->label('联系电话')
                            ->tel()
                            ->maxLength(32),
                        Select::make('org_id')
                            ->label('所在单位')
                            ->relationship('organization', 'name')
                            ->searchable()
                            ->preload(),
                        TextInput::make('org_name')
                            ->label('单位名称（未关联组织时填写）')
                            ->maxLength(255),
                        TextInput::make('department')
                            ->label('所在部门')
                            ->maxLength(255),
                        TextInput::make('position')
                            ->label('职务')
                            ->maxLength(128),
                        Select::make('cadre_level')
                            ->label('干部层级')
                            ->options([
                                '校领导' => '校领导',
                                '中层正职' => '中层正职',
                                '中层副职' => '中层副职',
                                '科级' => '科级',
                                '一般干部' => '一般干部',
                            ]),
                        Select::make('cadre_type')
                            ->label('干部类型')
                            ->options(Trainee::CADRE_TYPES)
                            ->default('党政管理干部')
                            ->required(),
                        Select::make('political_status')
                            ->label('政治面貌')
                            ->options(Trainee::POLITICAL_STATUSES)
                            ->default('群众')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (string $state, callable $set): void {
                                $set('is_party_member', in_array($state, ['中共党员', '中共预备党员'], true));
                            }),
                        Toggle::make('is_party_member')
                            ->label('是否党员'),
                        Select::make('member_id')
                            ->label('关联党员档案')
                            ->options(fn (): array => Member::query()
                                ->when(
                                    $orgIds = DataScope::visibleOrgIds(Auth::user()),
                                    fn (Builder $query) => $query->whereIn('org_id', $orgIds),
                                )
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->helperText('党员身份的学员关联党员档案；党外干部留空'),
                    ]),
                Section::make('管理信息')
                    ->columns(2)
                    ->schema([
                        Select::make('status')
                            ->label('状态')
                            ->options(Trainee::STATUSES)
                            ->default('在训')
                            ->required(),
                        Toggle::make('enabled')
                            ->label('启用')
                            ->default(true)
                            ->required(),
                        Select::make('data_source')
                            ->label('数据来源')
                            ->options([
                                '人事系统' => '人事系统',
                                '组织部导入' => '组织部导入',
                                '手工录入' => '手工录入',
                            ])
                            ->default('手工录入'),
                        Textarea::make('remark')
                            ->label('备注')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
