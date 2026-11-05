<?php

namespace App\Models;

use Database\Factories\SupervisionTaskFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 党建任务督办：组织部下发任务，到期前 7 天生成督办预警。
 */
class SupervisionTask extends Model
{
    /** @use HasFactory<SupervisionTaskFactory> */
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = [
        'title', 'content', 'org_id', 'due_date', 'status',
        'assignee', 'completed_at', 'completion_note', 'created_by',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    /** @var array<string, string> 任务状态 */
    public const array STATUSES = [
        '进行中' => '进行中',
        '已完成' => '已完成',
        '已逾期' => '已逾期',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    /**
     * 是否已逾期（截止日期已过且未完成）。
     */
    public function isOverdue(): bool
    {
        return $this->status !== '已完成' && $this->due_date !== null && now()->startOfDay()->gt($this->due_date);
    }

    /**
     * 按责任组织及下级组织过滤（数据范围收敛）。
     */
    public function scopeVisibleTo(Builder $query, array $orgIds): Builder
    {
        return $query->whereIn('org_id', $orgIds);
    }
}
