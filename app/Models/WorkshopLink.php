<?php

namespace App\Models;

use Database\Factories\WorkshopLinkFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 党务工作坊链接导航：分类、置顶、排序、点击计数。
 */
class WorkshopLink extends Model
{
    /** @use HasFactory<WorkshopLinkFactory> */
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = [
        'title', 'url', 'category', 'sort', 'is_pinned', 'click_count', 'enabled', 'created_by',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_pinned' => 'boolean',
        'enabled' => 'boolean',
    ];

    /** @var array<string, string> 链接分类 */
    public const array CATEGORIES = [
        '常用工具' => '常用工具',
        '学习资料' => '学习资料',
        '办事指南' => '办事指南',
        '其他' => '其他',
    ];
}
