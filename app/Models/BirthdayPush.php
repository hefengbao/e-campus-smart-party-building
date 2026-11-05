<?php

namespace App\Models;

use Database\Factories\BirthdayPushFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 政治生日推送台账：为未来 7 天内入党周年党员生成，幂等（member+birthday_date+channel）。
 */
class BirthdayPush extends Model
{
    /** @use HasFactory<BirthdayPushFactory> */
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = [
        'member_id', 'birthday_date', 'years', 'channel', 'content', 'status', 'pushed_at',
    ];

    /** @var array<string, string> */
    protected $attributes = [
        'status' => '待推送',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'birthday_date' => 'date',
        'pushed_at' => 'datetime',
    ];

    /** @var array<string, string> 推送状态 */
    public const array STATUSES = [
        '待推送' => '待推送',
        '已推送' => '已推送',
        '失败' => '失败',
    ];

    /** @var array<string, string> 推送渠道 */
    public const array CHANNELS = [
        '站内' => '站内',
        '门户' => '门户',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }
}
