<?php

namespace App\Models;

use Database\Factories\WorkshopReplyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 工作坊问答回帖：组织部回帖自动标记官方回复；楼主可标记最佳答案。
 */
class WorkshopReply extends Model
{
    /** @use HasFactory<WorkshopReplyFactory> */
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = [
        'post_id', 'content', 'author', 'author_name',
        'is_official', 'is_best', 'is_hidden',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_official' => 'boolean',
        'is_best' => 'boolean',
        'is_hidden' => 'boolean',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(WorkshopPost::class, 'post_id');
    }
}
