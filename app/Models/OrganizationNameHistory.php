<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationNameHistory extends Model
{
    /** @var array<int, string> */
    protected $fillable = ['organization_id', 'old_name', 'new_name', 'changed_on', 'changed_by'];

    /** @var array<string, string> */
    protected $casts = [
        'changed_on' => 'date',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
