<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhoneChangeRequest extends Model
{
    /** @var array<int, string> */
    protected $fillable = [
        'member_id', 'old_phone', 'new_phone', 'reason', 'status', 'review_note', 'reviewed_by', 'reviewed_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
