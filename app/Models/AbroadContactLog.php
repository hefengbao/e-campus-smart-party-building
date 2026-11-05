<?php

namespace App\Models;

use Database\Factories\AbroadContactLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 党组织与出国（境）党员定期联系记录。
 */
class AbroadContactLog extends Model
{
    /** @use HasFactory<AbroadContactLogFactory> */
    use HasFactory;

    public $timestamps = false;

    /** @var array<int, string> */
    protected $fillable = [
        'abroad_application_id', 'contact_date', 'method', 'content', 'operator',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'contact_date' => 'date',
        'created_at' => 'datetime',
    ];

    /** @var array<string, string> 联系方式 */
    public const array METHODS = [
        '电话' => '电话',
        '邮件' => '邮件',
        '视频' => '视频',
        '其他' => '其他',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(AbroadApplication::class, 'abroad_application_id');
    }
}
