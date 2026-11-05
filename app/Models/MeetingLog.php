<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 三会一课台账流转留痕：提交 / 退回修改 / 归档。
 */
class MeetingLog extends Model
{
    /** @var array<int, string> */
    protected $fillable = [
        'meeting_id', 'action', 'operator', 'role', 'comment',
    ];

    public $timestamps = false;

    /** @var array<string, string> */
    protected $casts = [
        'created_at' => 'datetime',
    ];

    /** @var array<string, string> 流转动作 */
    public const array ACTIONS = [
        '提交' => '提交',
        '退回修改' => '退回修改',
        '归档' => '归档',
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function operatorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator');
    }
}
