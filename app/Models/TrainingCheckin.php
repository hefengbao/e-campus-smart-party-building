<?php

namespace App\Models;

use Database\Factories\TrainingCheckinFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 培训签到（动态二维码 / 后台补录）。
 */
class TrainingCheckin extends Model
{
    /** @use HasFactory<TrainingCheckinFactory> */
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = [
        'training_id', 'member_id', 'checkin_at', 'method', 'status', 'operator',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'checkin_at' => 'datetime',
    ];

    /** @var array<string, string> 考勤状态 */
    public const array STATUSES = [
        '出勤' => '出勤',
        '迟到' => '迟到',
        '请假' => '请假',
        '缺席' => '缺席',
    ];

    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
