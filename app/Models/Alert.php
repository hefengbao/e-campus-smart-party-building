<?php

namespace App\Models;

use Database\Factories\AlertFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 监督预警：9 类规则扫描生成，dedup_key 幂等（重复扫描只刷新不新增），
 * 已处置/已忽略不复活。三级：提示/警告/严重。
 */
class Alert extends Model
{
    /** @use HasFactory<AlertFactory> */
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = [
        'rule', 'level', 'title', 'content', 'org_id', 'member_id',
        'biz_type', 'biz_id', 'dedup_key', 'status',
        'handled_by', 'handled_at', 'handle_note', 'last_scanned_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'handled_at' => 'datetime',
        'last_scanned_at' => 'datetime',
    ];

    /** @var array<string, string> 9 类预警规则 */
    public const array RULES = [
        '换届到期预警' => '换届到期预警',
        '组织生活提醒' => '组织生活提醒',
        '发展流程超期' => '发展流程超期',
        '发展材料缺失' => '发展材料缺失',
        '思想汇报逾期' => '思想汇报逾期',
        '发展条件不符' => '发展条件不符',
        '预备期到期' => '预备期到期',
        '学时未达标' => '学时未达标',
        '党建任务督办' => '党建任务督办',
    ];

    /** @var array<string, string> 预警级别 */
    public const array LEVELS = [
        '提示' => '提示',
        '警告' => '警告',
        '严重' => '严重',
    ];

    /** @var array<string, string> 处置状态 */
    public const array STATUSES = [
        '未处置' => '未处置',
        '已处置' => '已处置',
        '已忽略' => '已忽略',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    /**
     * 处置或忽略预警（幂等：已处置/已忽略不复活）。
     */
    public function handle(string $status, ?int $userId, ?string $note = null): void
    {
        if ($this->status !== '未处置') {
            return;
        }

        $this->forceFill([
            'status' => $status,
            'handled_by' => $userId !== null ? (string) $userId : null,
            'handled_at' => now(),
            'handle_note' => $note,
        ])->save();
    }

    /**
     * 按组织及下级组织过滤（数据范围收敛）。
     */
    public function scopeVisibleTo(Builder $query, array $orgIds): Builder
    {
        return $query->whereIn('org_id', $orgIds);
    }
}
