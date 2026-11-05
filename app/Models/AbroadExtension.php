<?php

namespace App\Models;

use Database\Factories\AbroadExtensionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 延长保留组织关系期限申请（院级审批）。
 */
class AbroadExtension extends Model
{
    /** @use HasFactory<AbroadExtensionFactory> */
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = [
        'abroad_application_id', 'extend_months', 'reason',
        'status', 'applied_at', 'approved_at', 'approver',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'applied_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    /** @var array<string, string> 延期状态 */
    public const array STATUSES = [
        '待审批' => '待审批',
        '已批准' => '已批准',
        '已驳回' => '已驳回',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(AbroadApplication::class, 'abroad_application_id');
    }
}
