<?php

namespace App\Models;

use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory, SoftDeletes;

    /** @var array<int, string> */
    protected $fillable = [
        'code', 'short_name', 'full_name', 'parent_id', 'level', 'path',
        'secretary', 'contact_person', 'contact_phone', 'founded_date',
        'term_start_date', 'term_years', 'term_end_date', 'nature', 'category',
        'member_type', 'status', 'sort_order', 'remark',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function committees(): HasMany
    {
        return $this->hasMany(OrganizationCommittee::class)->orderBy('sort_order');
    }

    public function nameHistories(): HasMany
    {
        return $this->hasMany(OrganizationNameHistory::class)->latest('changed_on');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'member_organization', 'organization_id', 'member_id')
            ->withPivot(['is_primary', 'status', 'start_date', 'end_date'])
            ->wherePivot('status', '正常')
            ->withTimestamps();
    }

    /**
     * 在册（正常状态）党员数。
     */
    public function activeMemberCount(): int
    {
        return $this->members()->where('members.status', '正常')->count();
    }

    /**
     * 按成员类型派生组织性质。
     */
    public static function natureFromMemberType(?string $memberType): ?string
    {
        return match ($memberType) {
            'tea' => '教工党支部',
            'stu' => '学生党支部',
            'mix' => '混合党支部',
            default => null,
        };
    }

    /**
     * 是否党支部层。
     */
    public function isBranch(): bool
    {
        return $this->level === 3;
    }

    /**
     * 撤销/合并预检：本组织及下级仍有在册党员时不可撤销。
     *
     * @return array{canRevoke: bool, activeMemberCount: int, sampleNames: array<int, string>}
     */
    public function revokeCheck(): array
    {
        $descendantIds = static::query()
            ->where('path', 'like', $this->getPathForQuery().'/%')
            ->pluck('id')
            ->push($this->id);

        $activeMembers = Member::query()
            ->whereHas('organizationLinks', fn (Builder $query) => $query
                ->whereIn('organization_id', $descendantIds)
                ->where('status', '正常'))
            ->where('members.status', '正常')
            ->limit(5)
            ->get(['id', 'name']);

        return [
            'canRevoke' => $activeMembers->isEmpty(),
            'activeMemberCount' => Member::query()
                ->whereHas('organizationLinks', fn (Builder $query) => $query
                    ->whereIn('organization_id', $descendantIds)
                    ->where('status', '正常'))
                ->where('members.status', '正常')
                ->count(),
            'sampleNames' => $activeMembers->pluck('name')->all(),
        ];
    }

    /**
     * 仅正常状态组织（默认范围）。
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', '正常');
    }

    /**
     * 后代组织（含自身）。
     */
    public function scopeWithDescendants(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->where('path', 'like', $this->getPathForQuery().'/%')
                ->orWhere('id', $this->id);
        });
    }

    protected function getPathForQuery(): string
    {
        return rtrim($this->path, '/');
    }
}
