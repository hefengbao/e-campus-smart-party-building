<?php

namespace App\Support;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * 数据范围收敛：按用户角色授权把查询限制在可见组织内。
 *
 * 规则（对齐 demo）：
 * - super_admin / org_dept：全校（org_id 为空的授权）。
 * - college / training_coordinator：授权组织 + 全部下级。
 * - branch：授权支部。
 * - member：仅本人数据（由调用方按 member_id 过滤）。
 */
class DataScope
{
    /**
     * 应用组织范围到查询。
     *
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public static function apply(Builder $query, User $user, string $orgColumn = 'org_id'): Builder
    {
        $orgIds = $user->scopedOrgIds();

        if ($orgIds === []) {
            return $query;
        }

        return $query->whereIn($orgColumn, $orgIds);
    }

    /**
     * 用户可见的组织 ID 集合（含下级），空数组 = 全校。
     *
     * @return array<int, int>
     */
    public static function visibleOrgIds(User $user): array
    {
        return $user->scopedOrgIds();
    }

    /**
     * 用户可见的组织树查询（用于组织管理）。
     *
     * @param  Builder<Organization>  $query
     * @return Builder<Organization>
     */
    public static function applyToOrganizations(Builder $query, User $user): Builder
    {
        $orgIds = $user->scopedOrgIds();

        if ($orgIds === []) {
            return $query;
        }

        return $query->whereIn('id', $orgIds);
    }
}
