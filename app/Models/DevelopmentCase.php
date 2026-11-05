<?php

namespace App\Models;

use Database\Factories\DevelopmentCaseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 党员发展流程实例（一人一案）。
 *
 * 五阶段 25 步骤（3/6/6/6/4），建档时按预置定义生成步骤记录，
 * 步骤完成自动推进；支持终止与归档。
 */
class DevelopmentCase extends Model
{
    /** @use HasFactory<DevelopmentCaseFactory> */
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = [
        'member_id', 'org_id', 'stage_no', 'step_no', 'status',
        'apply_date', 'started_at', 'finished_at', 'remark',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'apply_date' => 'date',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    /** @var array<string, string> 案件状态 */
    public const array STATUSES = [
        '进行中' => '进行中',
        '已完成' => '已完成',
        '已终止' => '已终止',
    ];

    /** @var array<int, array{int, string, int}> 五阶段定义（阶段号 => [起始步骤, 名称, 步骤数]） */
    public const array STAGES = [
        1 => [1, '入党申请', 2],
        2 => [3, '入党积极分子的确定和培养教育', 4],
        3 => [7, '发展对象的确定和考察', 5],
        4 => [12, '预备党员的接收', 7],
        5 => [19, '预备党员的教育考察和转正', 7],
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(DevelopmentStep::class, 'case_id')->orderBy('step_no');
    }

    /**
     * 当前步骤记录。
     */
    public function currentStep(): ?DevelopmentStep
    {
        return $this->steps()->where('step_no', $this->step_no)->first();
    }

    /**
     * 是否已终止或完成（不可再推进）。
     */
    public function isClosed(): bool
    {
        return $this->status !== '进行中';
    }

    /**
     * 是否超期（当前步骤计划完成日期已过且未完成）。
     */
    public function isOverdue(): bool
    {
        if ($this->isClosed()) {
            return false;
        }

        $current = $this->currentStep();

        return $current !== null
            && $current->planned_date !== null
            && $current->planned_date->isPast()
            && $current->status !== '已完成';
    }

    /**
     * 按组织及下级组织过滤（数据范围收敛）。
     */
    public function scopeVisibleTo(Builder $query, array $orgIds): Builder
    {
        return $query->whereIn('org_id', $orgIds);
    }
}
