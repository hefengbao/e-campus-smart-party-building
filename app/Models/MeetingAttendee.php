<?php

namespace App\Models;

use Database\Factories\MeetingAttendeeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 参会人员考勤：确认与签到。
 */
class MeetingAttendee extends Model
{
    /** @use HasFactory<MeetingAttendeeFactory> */
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = [
        'meeting_id', 'member_id', 'confirm_status', 'attendance_status',
        'sign_in_at', 'sign_in_method', 'leave_reason',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'sign_in_at' => 'datetime',
    ];

    /** @var array<string, string> 确认状态 */
    public const array CONFIRM_STATUSES = [
        '待确认' => '待确认',
        '参加' => '参加',
        '请假' => '请假',
    ];

    /** @var array<string, string> 考勤状态 */
    public const array ATTENDANCE_STATUSES = [
        '未签到' => '未签到',
        '出勤' => '出勤',
        '迟到' => '迟到',
        '请假' => '请假',
        '缺席' => '缺席',
    ];

    /** @var array<string, string> 签到方式 */
    public const array SIGN_IN_METHODS = [
        '二维码' => '二维码',
        '后台录入' => '后台录入',
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
