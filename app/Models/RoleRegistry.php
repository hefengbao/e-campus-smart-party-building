<?php

namespace App\Models;

use Illuminate\Support\Facades\Cache;

/**
 * 角色与权限注册表。
 *
 * 对齐 demo `party-api/app/permissions.py`：账号 = 人，权限 = 角色授权。
 * 层级授权链：super_admin → org_dept → college → branch → member；
 * training_coordinator 由 super_admin / org_dept / college 授予。
 */
class RoleRegistry
{
    /** @var array<string, int> 角色 → rank（越大越高） */
    public const RANKS = [
        'super_admin' => 5,
        'org_dept' => 4,
        'college' => 3,
        'training_coordinator' => 3,
        'branch' => 2,
        'member' => 1,
    ];

    /** @var array<string, string> 角色 → 中文名 */
    public const LABELS = [
        'super_admin' => '超级管理员',
        'org_dept' => '组织部管理员',
        'college' => '分党委管理员',
        'training_coordinator' => '培训协同员',
        'branch' => '支部管理员',
        'member' => '党员',
    ];

    /** @var array<string, string> 角色 → 面板 ID */
    public const PANELS = [
        'super_admin' => 'sysadmin',
        'org_dept' => 'org',
        'college' => 'college',
        'training_coordinator' => 'college',
        'branch' => 'branch',
        'member' => 'member',
    ];

    /** @var array<string, array<int, string>> 授予方 → 可授予角色 */
    public const GRANTABLE = [
        'super_admin' => ['org_dept', 'college', 'training_coordinator', 'branch', 'member'],
        'org_dept' => ['college', 'training_coordinator', 'branch', 'member'],
        'college' => ['branch', 'member'],
        'training_coordinator' => ['member'],
        'branch' => ['member'],
        'member' => [],
    ];

    /** @var array<string, int> 角色授权时数据范围组织的层级要求（null = 全校/不限） */
    public const ORG_LEVEL_REQUIREMENT = [
        'super_admin' => null,
        'org_dept' => null,
        'college' => 2,
        'training_coordinator' => null,
        'branch' => 3,
        'member' => 3,
    ];

    /**
     * 角色中文名。
     */
    public static function label(string $roleCode): string
    {
        return self::LABELS[$roleCode] ?? $roleCode;
    }

    /**
     * 角色对应面板 ID。
     */
    public static function panelFor(string $roleCode): string
    {
        return self::PANELS[$roleCode] ?? 'member';
    }

    /**
     * 指定角色持有的全部权限码。
     *
     * @return array<int, string>
     */
    public static function permissionsFor(string $roleCode): array
    {
        return Cache::rememberForever("role_permissions:{$roleCode}", function () use ($roleCode): array {
            $rank = self::RANKS[$roleCode] ?? 0;
            $permissions = [];

            foreach (PermissionRegistry::ALL as $permission => $minRank) {
                if ($rank >= $minRank) {
                    $permissions[] = $permission;
                }
            }

            // 培训协同员特例：可提交学时，但不可查看学员名册
            if ($roleCode === 'training_coordinator') {
                $excluded = ['member:read', 'member:read-all', 'member:create', 'member:update', 'member:delete',
                    'member:import', 'member:export', 'member:transfer', 'member:abroad', 'member:fee',
                    'member:phone-review', 'dev:read', 'dev:write', 'dev:submit', 'dev:archive', 'dev:reject',
                    'meeting:read', 'meeting:write', 'meeting:submit', 'meeting:archive', 'meeting:reject'];

                $permissions = array_values(array_diff($permissions, $excluded));

                $permissions = array_merge($permissions, [
                    'hour:submit', 'hour:read-self', 'trainee:lookup', 'training:read',
                ]);
            }

            return array_values(array_unique($permissions));
        });
    }

    /**
     * 权限检查：角色 rank 是否满足权限要求。
     */
    public static function roleHasPermission(string $roleCode, string $permission): bool
    {
        return in_array($permission, self::permissionsFor($roleCode), true);
    }

    /**
     * 授权校验：授予方能否授予目标角色（层级链）。
     */
    public static function canGrant(string $granterRole, string $targetRole): bool
    {
        return in_array($targetRole, self::GRANTABLE[$granterRole] ?? [], true);
    }
}
