<?php

namespace App\Models;

use Database\Factories\WorkshopActivityFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 党务工作坊活动：公开活动先审后发，不公开活动保存即发布。
 *
 * status 只存流程状态（草稿/待审核/已驳回/已发布）；
 * 展示状态由审核状态 + 时间推演：报名中 → 报名截止 → 进行中 → 已结束。
 * 总结归档限活动结束后 7 日内（组织部可事后补录）。
 */
class WorkshopActivity extends Model
{
    /** @use HasFactory<WorkshopActivityFactory> */
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = [
        'title', 'category', 'org_id', 'description', 'location',
        'start_at', 'end_at', 'enroll_deadline', 'is_public', 'cross_college',
        'max_enrollments', 'status', 'reject_reason', 'created_by',
        'reviewed_by', 'reviewed_at', 'published_at', 'summary', 'archived_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'enroll_deadline' => 'datetime',
        'reviewed_at' => 'datetime',
        'published_at' => 'datetime',
        'archived_at' => 'datetime',
        'is_public' => 'boolean',
        'cross_college' => 'boolean',
    ];

    /** @var array<string, string> 流程状态 */
    public const array STATUSES = [
        '草稿' => '草稿',
        '待审核' => '待审核',
        '已驳回' => '已驳回',
        '已发布' => '已发布',
    ];

    /** @var array<string, string> 活动分类 */
    public const array CATEGORIES = [
        '党务培训' => '党务培训',
        '经验交流' => '经验交流',
        '技能竞赛' => '技能竞赛',
        '其他' => '其他',
    ];

    /** @var int 总结归档窗口（天） */
    public const int ARCHIVE_WINDOW_DAYS = 7;

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(WorkshopEnrollment::class, 'activity_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 展示状态：流程状态优先，已发布后按时间推演。
     */
    public function effectiveStatus(): string
    {
        if ($this->status !== '已发布') {
            return $this->status;
        }

        $now = now();

        if ($this->end_at !== null && $now->gt($this->end_at)) {
            return '已结束';
        }

        if ($this->start_at !== null && $now->gte($this->start_at)) {
            return '进行中';
        }

        if ($this->enroll_deadline !== null && $now->gt($this->enroll_deadline)) {
            return '报名截止';
        }

        return '报名中';
    }

    /**
     * 是否可提交审核（草稿或已驳回）。
     */
    public function isSubmittable(): bool
    {
        return in_array($this->status, ['草稿', '已驳回'], true);
    }

    /**
     * 是否在总结归档窗口内（活动结束后 7 日内）。
     */
    public function isWithinArchiveWindow(): bool
    {
        if ($this->end_at === null) {
            return true;
        }

        return now()->diffInDays($this->end_at, false) >= -self::ARCHIVE_WINDOW_DAYS
            && now()->gte($this->end_at);
    }

    /**
     * 按发布组织及下级组织过滤（数据范围收敛）。
     */
    public function scopeVisibleTo(Builder $query, array $orgIds): Builder
    {
        return $query->whereIn('org_id', $orgIds);
    }
}
