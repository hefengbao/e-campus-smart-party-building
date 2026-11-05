<?php

namespace App\Models;

use Database\Factories\FeeRecordFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 党费记录：只读对接，fee/sync 端点接收推送，按工号/身份证匹配党员。
 * (member_id, period) 唯一，存在则更新否则插入。
 */
class FeeRecord extends Model
{
    /** @use HasFactory<FeeRecordFactory> */
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = [
        'member_id', 'period', 'amount', 'pay_status', 'pay_date', 'source',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'pay_date' => 'date',
        'amount' => 'decimal:2',
    ];

    /** @var array<string, string> 交纳状态 */
    public const array PAY_STATUSES = [
        '已交纳' => '已交纳',
        '未交纳' => '未交纳',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    /**
     * 按党员所在组织过滤（数据范围收敛）。
     */
    public function scopeVisibleTo(Builder $query, array $orgIds): Builder
    {
        return $query->whereHas('member', fn (Builder $q) => $q->whereIn('org_id', $orgIds));
    }
}
