<?php

namespace App\Models;

use Database\Factories\WorkshopEnrollmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 工作坊活动报名：单位名义报名，跨院报名需发布方（或组织部）审核。
 */
class WorkshopEnrollment extends Model
{
    /** @use HasFactory<WorkshopEnrollmentFactory> */
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = [
        'activity_id', 'org_id', 'contact_user', 'headcount',
        'status', 'remark', 'reviewed_by', 'reviewed_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    /** @var array<string, string> 报名状态 */
    public const array STATUSES = [
        '待审核' => '待审核',
        '已通过' => '已通过',
        '已驳回' => '已驳回',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(WorkshopActivity::class, 'activity_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    /**
     * 按报名单位过滤（数据范围收敛）。
     */
    public function scopeVisibleTo(Builder $query, array $orgIds): Builder
    {
        return $query->whereIn('org_id', $orgIds);
    }
}
