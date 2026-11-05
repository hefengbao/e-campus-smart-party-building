<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 发展流程步骤记录：材料、时限、审批留痕。
 *
 * 每案 25 条（case_id × step_no 唯一），完成当前步骤自动推进到下一步。
 */
class DevelopmentStep extends Model
{
    /** @var array<int, string> */
    protected $fillable = [
        'case_id', 'stage_no', 'step_no', 'step_name', 'status',
        'planned_date', 'done_date', 'operator', 'opinion', 'materials', 'detail',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'planned_date' => 'date',
        'done_date' => 'date',
        'materials' => 'array',
        'detail' => 'array',
    ];

    /** @var array<string, string> 步骤状态 */
    public const array STATUSES = [
        '未开始' => '未开始',
        '进行中' => '进行中',
        '已完成' => '已完成',
        '逾期' => '逾期',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(DevelopmentCase::class, 'case_id');
    }
}
