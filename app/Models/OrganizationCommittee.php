<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationCommittee extends Model
{
    /** @var array<int, string> */
    protected $fillable = ['organization_id', 'name', 'position', 'phone', 'term_start', 'term_end', 'sort_order'];

    /** @var array<string, string> */
    protected $casts = [
        'term_start' => 'date',
        'term_end' => 'date',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
