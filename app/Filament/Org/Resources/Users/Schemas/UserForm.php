<?php

namespace App\Filament\Org\Resources\Users\Schemas;

use App\Models\Organization;
use App\Models\RoleRegistry;
use App\Models\User;
use App\Support\DataScope;
use Closure;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('账号信息')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('姓名')
                            ->required()
                            ->maxLength(50),
                        TextInput::make('email')
                            ->label('邮箱')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(100),
                        TextInput::make('password')
                            ->label('密码')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->minLength(8)
                            ->maxLength(255)
                            ->hintIcon('heroicon-m-information-circle', tooltip: '编辑时留空表示不修改密码'),
                        TextInput::make('phone')
                            ->label('联系电话')
                            ->tel()
                            ->maxLength(30),
                        Select::make('member_id')
                            ->label('关联党员档案')
                            ->relationship('member', 'name')
                            ->searchable()
                            ->preload()
                            ->getOptionLabelFromRecordUsing(fn ($record): string => sprintf(
                                '%s（%s）',
                                $record->name,
                                $record->staff_no,
                            ))
                            ->hintIcon('heroicon-m-link', tooltip: '账号与党员档案一一对应；关联后可凭在任委员身份进入支部/分党委面板'),
                    ]),
                Section::make('角色授权')
                    ->columns(1)
                    ->description('层级授权链：超级管理员 → 组织部 → 分党委 → 支部 → 党员；培训协同员由组织部及以上授予')
                    ->schema([
                        Repeater::make('roleGrants')
                            ->label('授权记录')
                            ->relationship()
                            ->columns(2)
                            ->schema([
                                Select::make('role_code')
                                    ->label('角色')
                                    ->options(self::grantableOptions())
                                    ->required()
                                    ->live()
                                    ->rule(self::grantRule()),
                                Select::make('org_id')
                                    ->label('授权组织')
                                    ->relationship(
                                        name: 'organization',
                                        titleAttribute: 'short_name',
                                        modifyQueryUsing: fn (Builder $query) => DataScope::applyToOrganizations(
                                            $query,
                                            Auth::user(),
                                        ),
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->hintIcon('heroicon-m-information-circle', tooltip: '分党委角色须选二级党委，支部/党员角色须选党支部'),
                                Toggle::make('is_primary')
                                    ->label('主角色')
                                    ->default(true)
                                    ->inline(false),
                                Select::make('status')
                                    ->label('状态')
                                    ->options(['正常' => '正常', '停用' => '停用'])
                                    ->required()
                                    ->default('正常')
                                    ->native(false),
                                TextInput::make('remark')
                                    ->label('备注')
                                    ->maxLength(100)
                                    ->columnSpanFull(),
                            ])
                            ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => array_merge($data, [
                                'granted_by' => Auth::id(),
                            ]))
                            ->defaultItems(0)
                            ->addActionLabel('添加授权'),
                    ]),
            ]);
    }

    /**
     * 当前用户可授予的角色选项。
     *
     * @return array<string, string>
     */
    private static function grantableOptions(): array
    {
        $user = self::currentUser();

        if ($user === null) {
            return [];
        }

        return collect($user->roleCodes())
            ->flatMap(fn (string $role): array => RoleRegistry::GRANTABLE[$role] ?? [])
            ->unique()
            ->sortKeys()
            ->mapWithKeys(fn (string $role): array => [$role => RoleRegistry::label($role)])
            ->all();
    }

    /**
     * 授权三重校验：授予链 + 组织层级要求 + 目标组织存在性。
     */
    private static function grantRule(): Closure
    {
        return function (Get $get): Closure {
            return function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                if (! filled($value)) {
                    return;
                }

                $user = self::currentUser();

                $canGrant = $user !== null && collect($user->roleCodes())
                    ->contains(fn (string $role): bool => RoleRegistry::canGrant($role, (string) $value));

                if (! $canGrant) {
                    $fail('当前账号无权授予角色「'.RoleRegistry::label((string) $value).'」');

                    return;
                }

                $requiredLevel = RoleRegistry::ORG_LEVEL_REQUIREMENT[$value] ?? null;

                if ($requiredLevel !== null) {
                    $orgId = $get('org_id');

                    if (! filled($orgId)) {
                        $fail('该角色必须指定授权组织');

                        return;
                    }

                    $org = Organization::find($orgId);
                    $levelNames = [1 => '学校党委', 2 => '二级党委', 3 => '党支部'];

                    if ($org === null || (int) $org->level !== $requiredLevel) {
                        $fail('该角色要求授权组织层级为「'.($levelNames[$requiredLevel] ?? '未知').'」');
                    }
                }
            };
        };
    }

    private static function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }
}
