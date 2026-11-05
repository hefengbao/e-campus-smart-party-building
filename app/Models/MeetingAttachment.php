<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 会议材料归档：纪要 / 影像 / 签到表。
 */
class MeetingAttachment extends Model
{
    /** @var array<int, string> */
    protected $fillable = [
        'meeting_id', 'kind', 'name', 'file_path', 'uploaded_by',
    ];

    /** @var array<string, string> 材料类型 */
    public const array KINDS = [
        '纪要' => '纪要',
        '影像' => '影像',
        '签到表' => '签到表',
        '其他' => '其他',
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
