<?php

namespace App\Models;

use Database\Factories\WorkshopPostFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 工作坊问答帖：标签必填单选，敏感词过滤，组织部可置顶/隐藏。
 */
class WorkshopPost extends Model
{
    /** @use HasFactory<WorkshopPostFactory> */
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = [
        'title', 'content', 'tag', 'author', 'author_name',
        'is_pinned', 'is_hidden', 'is_resolved', 'view_count',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_pinned' => 'boolean',
        'is_hidden' => 'boolean',
        'is_resolved' => 'boolean',
    ];

    /** @var array<string, string> 标签（必填单选） */
    public const array TAGS = [
        '党员发展' => '党员发展',
        '组织关系' => '组织关系',
        '教育培训' => '教育培训',
        '换届选举' => '换届选举',
        '党费管理' => '党费管理',
        '其他' => '其他',
    ];

    public function authorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(WorkshopReply::class, 'post_id');
    }

    /**
     * 只看可见帖（未隐藏）。
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_hidden', false);
    }
}
