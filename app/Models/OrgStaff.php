<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 组织部工作人员：控制 org panel 进入权限。
 */
class OrgStaff extends Model
{
    /** @var array<int, string> */
    protected $fillable = ['user_id', 'name', 'position', 'phone', 'status'];

    /** @var array<string, string> */
    public const array STATUSES = [
        '在职' => '在职',
        '离职' => '离职',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 是否在职。
     */
    public function isActive(): bool
    {
        return $this->status === '在职';
    }
}
