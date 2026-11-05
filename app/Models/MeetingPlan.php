<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 年度组织生活计划：用于"未按期开展"提醒。
 */
class MeetingPlan extends Model
{
    /** @var array<int, string> */
    protected $fillable = [
        'org_id', 'year', 'month', 'meeting_type', 'planned_times', 'note',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }
}
