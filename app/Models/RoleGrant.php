<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoleGrant extends Model
{
    /** @var array<int, string> */
    protected $fillable = [
        'user_id', 'role_code', 'org_id', 'is_primary', 'status', 'remark', 'granted_by',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    /**
     * 角色中文名。
     */
    public function getRoleLabelAttribute(): string
    {
        return RoleRegistry::label($this->role_code);
    }
}
