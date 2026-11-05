<?php

namespace App\Models;

use Database\Factories\TransferFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 组织关系转接（转出/转入，三级审批：支部→院级→组织部）。
 *
 * 办结时生成介绍信编号。
 */
class Transfer extends Model
{
    /** @use HasFactory<TransferFactory> */
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = [
        'member_id', 'direction', 'transfer_type', 'from_org_id',
        'to_org_name', 'to_org_code', 'reason', 'apply_at', 'status',
        'branch_reviewer', 'branch_reviewed_at',
        'college_reviewer', 'college_reviewed_at',
        'dept_reviewer', 'dept_reviewed_at',
        'letter_no', 'finished_at', 'remark',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'apply_at' => 'datetime',
        'branch_reviewed_at' => 'datetime',
        'college_reviewed_at' => 'datetime',
        'dept_reviewed_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    /** @var array<string, string> 转接状态 */
    public const array STATUSES = [
        '待支部审核' => '待支部审核',
        '待院级审核' => '待院级审核',
        '待组织部审核' => '待组织部审核',
        '已办结' => '已办结',
        '已驳回' => '已驳回',
    ];

    /** @var array<string, string> 转接方向 */
    public const array DIRECTIONS = [
        '转出' => '转出',
        '转入' => '转入',
    ];

    /** @var array<string, string> 转接类型 */
    public const array TRANSFER_TYPES = [
        '校内' => '校内',
        '省内' => '省内',
        '跨省' => '跨省',
        '校外' => '校外',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function fromOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'from_org_id');
    }

    /**
     * 逐级审批推进：支部→院级→组织部→办结。
     */
    public function review(bool $approve, string $stage, int $reviewerId, ?string $note = null): void
    {
        if (! $approve) {
            $this->update([
                'status' => '已驳回',
                'remark' => $note ?? $this->remark,
                ...match ($stage) {
                    'branch' => ['branch_reviewer' => $reviewerId, 'branch_reviewed_at' => now()],
                    'college' => ['college_reviewer' => $reviewerId, 'college_reviewed_at' => now()],
                    default => ['dept_reviewer' => $reviewerId, 'dept_reviewed_at' => now()],
                },
            ]);

            return;
        }

        $nextStatus = match ($stage) {
            'branch' => '待院级审核',
            'college' => '待组织部审核',
            default => '已办结',
        };

        $updates = [
            'status' => $nextStatus,
            ...match ($stage) {
                'branch' => ['branch_reviewer' => $reviewerId, 'branch_reviewed_at' => now()],
                'college' => ['college_reviewer' => $reviewerId, 'college_reviewed_at' => now()],
                default => ['dept_reviewer' => $reviewerId, 'dept_reviewed_at' => now()],
            },
        ];

        if ($nextStatus === '已办结') {
            $updates['letter_no'] = $this->generateLetterNo();
            $updates['finished_at'] = now();
        }

        $this->update($updates);
    }

    /**
     * 生成介绍信编号：ZZJD-年份-6 位流水。
     */
    protected function generateLetterNo(): string
    {
        $year = now()->format('Y');
        $seq = static::query()->whereYear('finished_at', $year)->count() + 1;

        return sprintf('ZZJD-%s-%06d', $year, $seq);
    }

    /**
     * 按组织及下级组织过滤（转出组织）。
     */
    public function scopeVisibleTo(Builder $query, array $orgIds): Builder
    {
        return $query->whereIn('from_org_id', $orgIds);
    }
}
