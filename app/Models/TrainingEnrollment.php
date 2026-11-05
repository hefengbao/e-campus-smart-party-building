<?php

namespace App\Models;

use Database\Factories\TrainingEnrollmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 培训报名与逐级审批（支部→院级）。
 */
class TrainingEnrollment extends Model
{
    /** @use HasFactory<TrainingEnrollmentFactory> */
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = [
        'training_id', 'member_id', 'status', 'applied_at',
        'branch_reviewer', 'branch_reviewed_at',
        'college_reviewer', 'college_reviewed_at', 'remark',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'applied_at' => 'datetime',
        'branch_reviewed_at' => 'datetime',
        'college_reviewed_at' => 'datetime',
    ];

    /** @var array<string, string> 报名状态 */
    public const array STATUSES = [
        '待支部审核' => '待支部审核',
        '待院级审核' => '待院级审核',
        '已通过' => '已通过',
        '已驳回' => '已驳回',
    ];

    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * 支部审核通过 → 流转院级；驳回 → 终止。
     */
    public function reviewBranch(bool $approve, int $reviewerId, ?string $note = null): void
    {
        $this->update([
            'status' => $approve ? '待院级审核' : '已驳回',
            'branch_reviewer' => $reviewerId,
            'branch_reviewed_at' => now(),
            'remark' => $note ?? $this->remark,
        ]);
    }

    /**
     * 院级审核通过/驳回（终审）。
     */
    public function reviewCollege(bool $approve, int $reviewerId, ?string $note = null): void
    {
        $this->update([
            'status' => $approve ? '已通过' : '已驳回',
            'college_reviewer' => $reviewerId,
            'college_reviewed_at' => now(),
            'remark' => $note ?? $this->remark,
        ]);
    }

    /**
     * 按组织及下级组织过滤（经培训项目归属组织）。
     */
    public function scopeVisibleTo(Builder $query, array $orgIds): Builder
    {
        return $query->whereHas('training', fn (Builder $q) => $q->whereIn('org_id', $orgIds));
    }
}
