<?php

namespace App\Models;

use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 课程资源（党史教育、理论解读等）。
 */
class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = [
        'title', 'category', 'provider_level', 'org_id', 'hours',
        'resource_url', 'description', 'tags', 'publish',
        'view_count', 'created_by',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'tags' => 'array',
        'publish' => 'boolean',
    ];

    /** @var array<string, string> 课程分类 */
    public const array CATEGORIES = [
        '党课' => '党课',
        '党史教育' => '党史教育',
        '理论解读' => '理论解读',
        '业务能力' => '业务能力',
        '时政' => '时政',
    ];

    /** @var array<string, string> 提供层级 */
    public const array PROVIDER_LEVELS = [
        '校级' => '校级',
        '院级' => '院级',
        '支部' => '支部',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    /**
     * 仅已发布课程。
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('publish', true);
    }
}
