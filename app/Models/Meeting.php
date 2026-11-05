<?php

namespace App\Models;

use Database\Factories\MeetingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 三会一课会议台账：支部党员大会/支部委员会/党小组会/党课/主题党日/其他。
 *
 * 状态机：草稿 → 已提交 → 已归档 / 退回修改（退回后可再次提交）。
 * 应到人数由本支部正式党员人数自动统计，不允许前端修改。
 */
class Meeting extends Model
{
    /** @use HasFactory<MeetingFactory> */
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = [
        'org_id', 'meeting_type', 'title', 'planned_at', 'actual_at', 'location',
        'host', 'recorder', 'status', 'summary', 'key_points', 'expected_count',
        'observers', 'content_items', 'submitted_at', 'submitted_by',
        'reviewed_at', 'reviewed_by', 'review_comment', 'created_by',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'planned_at' => 'datetime',
        'actual_at' => 'datetime',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'key_points' => 'array',
        'content_items' => 'array',
    ];

    /** @var array<string, string> 6 类会议 */
    public const array MEETING_TYPES = [
        '支部党员大会' => '支部党员大会',
        '支部委员会' => '支部委员会',
        '党小组会' => '党小组会',
        '党课' => '党课',
        '主题党日' => '主题党日',
        '其他' => '其他',
    ];

    /** @var array<string, string> 状态机 */
    public const array STATUSES = [
        '草稿' => '草稿',
        '已提交' => '已提交',
        '退回修改' => '退回修改',
        '已归档' => '已归档',
    ];

    /** @var array<int, string> 可提交的状态 */
    public const array SUBMITTABLE_STATUSES = ['草稿', '退回修改'];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(MeetingAttendee::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(MeetingAttachment::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(MeetingLog::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * 是否可提交（草稿或退回修改状态）。
     */
    public function isSubmittable(): bool
    {
        return in_array($this->status, self::SUBMITTABLE_STATUSES, true);
    }

    /**
     * 是否已归档。
     */
    public function isArchived(): bool
    {
        return $this->status === '已归档';
    }

    /**
     * 按组织及下级组织过滤（数据范围收敛）。
     */
    public function scopeVisibleTo(Builder $query, array $orgIds): Builder
    {
        return $query->whereIn('org_id', $orgIds);
    }
}
