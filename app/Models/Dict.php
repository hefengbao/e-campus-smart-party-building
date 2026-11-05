<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dict extends Model
{
    /** @var array<int, string> */
    protected $fillable = ['type', 'key', 'label', 'sort_order', 'enabled'];

    /** @var array<string, string> */
    protected $casts = [
        'enabled' => 'boolean',
    ];
}
