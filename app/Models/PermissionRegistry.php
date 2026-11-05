<?php

namespace App\Models;

/**
 * 权限注册表：权限码 → 最低角色 rank。
 *
 * 对齐 demo `party-api/app/permissions.py` 的 96 项权限。
 * rank：super_admin=5, org_dept=4, college=3, training_coordinator=3, branch=2, member=1。
 */
class PermissionRegistry
{
    /** @var array<string, int> */
    public const ALL = [
        // ---- 组织管理 ----
        'org:read' => 2,
        'org:read-all' => 4,
        'org:create' => 4,
        'org:update' => 4,
        'org:update-code' => 4,
        'org:revoke' => 4,
        'org:merge' => 4,
        'org:committee' => 4,
        'org:history' => 4,
        'org:term' => 4,
        // ---- 党员管理 ----
        'member:read' => 2,
        'member:read-all' => 4,
        'member:create' => 4,
        'member:update' => 4,
        'member:delete' => 4,
        'member:import' => 4,
        'member:export' => 4,
        'member:transfer' => 4,
        'member:abroad' => 4,
        'member:fee' => 4,
        'member:phone-review' => 4,
        // ---- 发展党员 ----
        'dev:read' => 2,
        'dev:write' => 2,
        'dev:submit' => 2,
        'dev:archive' => 2,
        'dev:reject' => 2,
        'dev:review' => 3,
        // ---- 组织生活 ----
        'meeting:read' => 2,
        'meeting:write' => 2,
        'meeting:submit' => 2,
        'meeting:archive' => 2,
        'meeting:reject' => 2,
        'meeting:review' => 3,
        // ---- 教育培训 ----
        'training:read' => 2,
        'training:write' => 3,
        'training:submit' => 3,
        'training:review' => 3,
        'trainee:lookup' => 3,
        'trainee:write' => 3,
        // ---- 学时管理 ----
        'hour:read' => 2,
        'hour:read-self' => 1,
        'hour:write' => 2,
        'hour:submit' => 3,
        'hour:review' => 3,
        'hour:export' => 4,
        // ---- 党费 ----
        'fee:read' => 4,
        'fee:sync' => 4,
        // ---- 出国（境） ----
        'abroad:read' => 4,
        'abroad:write' => 4,
        'abroad:review' => 4,
        // ---- 预警提醒 ----
        'alert:read' => 3,
        'alert:handle' => 3,
        'alert:config' => 4,
        // ---- 生日关怀 ----
        'birthday:read' => 3,
        'birthday:push' => 3,
        // ---- 工作坊 ----
        'workshop:read' => 1,
        'workshop:write' => 1,
        'workshop:submit' => 1,
        'workshop:review' => 3,
        'workshop:archive' => 3,
        // ---- 统计看板 ----
        'dashboard:read' => 2,
        'dashboard:read-all' => 4,
        // ---- 账号与授权 ----
        'account:read' => 4,
        'account:create' => 4,
        'account:update' => 4,
        'account:disable' => 4,
        'account:reset-password' => 4,
        'grant:read' => 4,
        'grant:write' => 4,
        // ---- 系统管理 ----
        'dict:read' => 4,
        'dict:write' => 5,
        'config:read' => 4,
        'config:write' => 5,
        'audit:read' => 5,
        'system:sync' => 5,
    ];

    /**
     * 全部权限码。
     *
     * @return array<int, string>
     */
    public static function all(): array
    {
        return array_keys(self::ALL);
    }

    /**
     * 权限是否满足最低 rank。
     */
    public static function satisfies(string $permission, int $rank): bool
    {
        $minRank = self::ALL[$permission] ?? null;

        return $minRank !== null && $rank >= $minRank;
    }
}
