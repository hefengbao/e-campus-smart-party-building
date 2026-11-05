<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 系统管理员：控制 sysadmin panel 进入权限。
 */
class Admin extends Model
{
    /** @var array<int, string> */
    protected $fillable = ['user_id', 'name', 'status'];

    /** @var array<string, string> */
    public const array STATUSES = [
        '启用' => '启用',
        '停用' => '停用',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 是否启用。
     */
    public function isActive(): bool
    {
        return $this->status === '启用';
    }
}
