<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 委员会成员：党支部委员会 / 党委（二级）委员会共用。
 *
 * 通过 organization.level 区分：3 = 党支部委员会，2 = 党委委员会。
 * 职务可配置（党支部书记/副书记/组织委员/宣传委员/党委书记等），
 * 任命方式：选举/指派；在任状态决定 branch / college 面板进入权限。
 */
class CommitteeMember extends Model
{
    /** @var array<int, string> */
    protected $fillable = [
        'organization_id', 'member_id', 'position', 'appointment_method',
        'term_start', 'term_end', 'status', 'sort_order',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'term_start' => 'date',
        'term_end' => 'date',
    ];

    /** @var array<int, string> 党支部委员会职务（可配置，存于字典 committee_branch_position） */
    public const array BRANCH_POSITIONS = [
        '党支部书记', '党支部副书记', '组织委员', '宣传委员', '纪检委员', '青年委员',
    ];

    /** @var array<int, string> 党委委员会职务（可配置，存于字典 committee_college_position） */
    public const array COLLEGE_POSITIONS = [
        '党委书记', '党委副书记', '组织委员', '宣传委员', '纪检委员',
    ];

    /** @var array<string, string> 任命方式 */
    public const array APPOINTMENT_METHODS = [
        '选举' => '选举',
        '指派' => '指派',
    ];

    /** @var array<string, string> 在任状态 */
    public const array STATUSES = [
        '在任' => '在任',
        '离任' => '离任',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * 是否在任（term_end 为空或未到期，且状态为在任）。
     */
    public function isActive(): bool
    {
        if ($this->status !== '在任') {
            return false;
        }

        return $this->term_end === null || $this->term_end->isFuture();
    }

    /**
     * 按委员会类型（branch / college）过滤。
     */
    public function scopeOfCommitteeType($query, string $type)
    {
        $level = $type === 'branch' ? 3 : 2;

        return $query->whereHas('organization', fn ($q) => $q->where('level', $level));
    }

    /**
     * 在任成员。
     */
    public function scopeActive($query)
    {
        return $query->where('status', '在任')
            ->where(fn ($q) => $q->whereNull('term_end')->orWhere('term_end', '>=', now()->toDateString()));
    }
}
