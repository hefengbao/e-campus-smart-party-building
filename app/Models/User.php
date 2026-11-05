<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

#[Fillable(['name', 'email', 'password', 'phone', 'sso_subject', 'member_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** 主键为工号/学号（字符串）。 */
    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * 角色授权记录。
     */
    public function roleGrants(): HasMany
    {
        return $this->hasMany(RoleGrant::class);
    }

    /**
     * 关联党员档案（一一对应，外键 members.user_id）。
     */
    public function member(): HasOne
    {
        return $this->hasOne(Member::class, 'user_id');
    }

    /**
     * 组织部工作人员档案（控制 org panel 进入权限）。
     */
    public function orgStaff(): HasOne
    {
        return $this->hasOne(OrgStaff::class);
    }

    /**
     * 系统管理员档案（控制 sysadmin panel 进入权限）。
     */
    public function admin(): HasOne
    {
        return $this->hasOne(Admin::class);
    }

    /**
     * 生效的角色授权（正常状态）。
     */
    public function activeRoleGrants(): Collection
    {
        return $this->roleGrants()->where('status', '正常')->get();
    }

    /**
     * 用户全部角色码。
     *
     * @return array<int, string>
     */
    public function roleCodes(): array
    {
        return $this->activeRoleGrants()
            ->pluck('role_code')
            ->unique()
            ->all();
    }

    /**
     * 用户最高角色 rank。
     */
    public function maxRank(): int
    {
        $ranks = array_map(
            fn (string $role): int => RoleRegistry::RANKS[$role] ?? 0,
            $this->roleCodes(),
        );

        return $ranks === [] ? 0 : max($ranks);
    }

    /**
     * 是否持有某角色。
     */
    public function hasRole(string $roleCode): bool
    {
        return in_array($roleCode, $this->roleCodes(), true);
    }

    /**
     * 是否持有任一角色。
     *
     * @param  array<int, string>  $roleCodes
     */
    public function hasAnyRole(array $roleCodes): bool
    {
        return $this->roleCodes() !== [] && array_intersect($roleCodes, $this->roleCodes()) !== [];
    }

    /**
     * 权限检查：任一角色持有该权限即通过。
     *
     * @param  array  $arguments
     */
    public function can($abilities, $arguments = []): bool
    {
        if (is_string($abilities) && str_contains($abilities, ':')) {
            return collect($this->roleCodes())
                ->contains(fn (string $role): bool => RoleRegistry::roleHasPermission($role, $abilities));
        }

        return parent::can($abilities, $arguments);
    }

    /**
     * 数据范围组织 ID（含下级），空数组 = 全校。
     *
     * 优先按角色授权计算；无任何角色授权时（新架构下委员/普通用户不再持有角色），
     * 回退为在任委员所在组织及其全部下级。
     *
     * @return array<int, int>
     */
    public function scopedOrgIds(): array
    {
        $grants = $this->activeRoleGrants();

        if ($grants->isEmpty()) {
            return $this->committeeScopedOrgIds();
        }

        // 任一授权为全校（org_id 为空）即全校可见
        if ($grants->contains(fn (RoleGrant $grant): bool => $grant->org_id === null)) {
            return [];
        }

        $orgIds = [];

        foreach ($grants as $grant) {
            if ($grant->org_id === null) {
                continue;
            }

            $orgIds = array_merge($orgIds, $this->orgAndDescendantIds($grant->org_id));
        }

        return array_values(array_unique(array_map(intval(...), $orgIds)));
    }

    /**
     * 委员会数据范围回退：在任委员所在组织及其全部下级。
     *
     * @return array<int, int>
     */
    private function committeeScopedOrgIds(): array
    {
        $memberId = $this->member_id;

        if ($memberId === null) {
            return [];
        }

        $committeeOrgIds = CommitteeMember::query()
            ->where('member_id', $memberId)
            ->where('status', '在任')
            ->where(fn ($query) => $query->whereNull('term_end')->orWhere('term_end', '>=', now()->toDateString()))
            ->pluck('organization_id')
            ->unique()
            ->all();

        if ($committeeOrgIds === []) {
            return [];
        }

        $orgIds = [];

        foreach ($committeeOrgIds as $orgId) {
            $orgIds = array_merge($orgIds, $this->orgAndDescendantIds((int) $orgId));
        }

        return array_values(array_unique(array_map(intval(...), $orgIds)));
    }

    /**
     * 指定组织及其全部下级的组织 ID。
     *
     * @return array<int, int>
     */
    private function orgAndDescendantIds(int $orgId): array
    {
        $org = Organization::find($orgId);

        if ($org === null) {
            return [];
        }

        // 本组织 + 全部下级
        return Organization::query()
            ->where('path', 'like', rtrim($org->path, '/').'/%')
            ->orWhere('id', $org->id)
            ->pluck('id')
            ->all();
    }

    /**
     * Filament 面板访问控制（按新架构）：
     *
     * - member 面板：默认面板，所有登录用户可进入。
     * - branch 面板：本人关联党员在任党支部委员会委员。
     * - college 面板：本人关联党员在任党委（二级）委员会委员。
     * - org 面板：存在在职的组织部工作人员记录。
     * - sysadmin 面板：存在启用的系统管理员记录。
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'member' => true,
            'branch' => $this->hasActiveCommitteeMembership(3),
            'college' => $this->hasActiveCommitteeMembership(2),
            'org' => $this->orgStaff()->where('status', '在职')->exists(),
            'sysadmin' => $this->admin()->where('status', '启用')->exists(),
            default => false,
        };
    }

    /**
     * 本人关联党员是否在某层级组织的委员会中在任。
     *
     * @param  int  $level  3 = 党支部委员会，2 = 党委委员会
     */
    private function hasActiveCommitteeMembership(int $level): bool
    {
        $memberId = $this->member_id;

        if ($memberId === null) {
            return false;
        }

        return CommitteeMember::query()
            ->where('member_id', $memberId)
            ->where('status', '在任')
            ->where(fn ($query) => $query->whereNull('term_end')->orWhere('term_end', '>=', now()->toDateString()))
            ->whereHas('organization', fn ($query) => $query->where('level', $level))
            ->exists();
    }
}
