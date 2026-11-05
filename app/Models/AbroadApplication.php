<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\AbroadApplicationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 因私出国（境）审批。
 *
 * 按天数自动分流审批路径：<6 个月走支部→院级；≥6 个月加组织部终审。
 * 支持延期申请、销假、定期联系记录。
 */
class AbroadApplication extends Model
{
    /** @use HasFactory<AbroadApplicationFactory> */
    use HasFactory;

    /** 六个月天数阈值（审批分流）。 */
    public const int SIX_MONTH_DAYS = 183;

    /** @var array<int, string> */
    protected $fillable = [
        'member_id', 'destination', 'reason', 'start_date', 'end_date',
        'duration_days', 'approval_route', 'retain_relation', 'contact_phone',
        'status', 'branch_reviewer', 'branch_reviewed_at',
        'college_reviewer', 'college_reviewed_at',
        'dept_reviewer', 'dept_reviewed_at',
        'return_date', 'cancel_note',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'return_date' => 'date',
        'branch_reviewed_at' => 'datetime',
        'college_reviewed_at' => 'datetime',
        'dept_reviewed_at' => 'datetime',
        'retain_relation' => 'boolean',
    ];

    /** @var array<string, string> 申请状态 */
    public const array STATUSES = [
        '待支部审核' => '待支部审核',
        '待院级审核' => '待院级审核',
        '待组织部审核' => '待组织部审核',
        '已批准' => '已批准',
        '已驳回' => '已驳回',
        '已销假' => '已销假',
    ];

    /** @var array<string, string> 审批路径 */
    public const array APPROVAL_ROUTES = [
        '6个月以下' => '6个月以下',
        '6个月及以上' => '6个月及以上',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function extensions(): HasMany
    {
        return $this->hasMany(AbroadExtension::class, 'abroad_application_id');
    }

    public function contactLogs(): HasMany
    {
        return $this->hasMany(AbroadContactLog::class, 'abroad_application_id');
    }

    /**
     * 按出发/返回日期计算天数并判定审批路径。
     */
    public static function resolveRoute(string $startDate, string $endDate): array
    {
        $days = (int) (Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1);

        return [
            'duration_days' => $days,
            'approval_route' => $days >= self::SIX_MONTH_DAYS ? '6个月及以上' : '6个月以下',
        ];
    }

    /**
     * 逐级审批推进：<6 个月支部→院级→批准；≥6 个月支部→院级→组织部→批准。
     */
    public function review(bool $approve, string $stage, int $reviewerId, ?string $note = null): void
    {
        if (! $approve) {
            $this->update([
                'status' => '已驳回',
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
            'college' => $this->approval_route === '6个月及以上' ? '待组织部审核' : '已批准',
            default => '已批准',
        };

        $this->update([
            'status' => $nextStatus,
            ...match ($stage) {
                'branch' => ['branch_reviewer' => $reviewerId, 'branch_reviewed_at' => now()],
                'college' => ['college_reviewer' => $reviewerId, 'college_reviewed_at' => now()],
                default => ['dept_reviewer' => $reviewerId, 'dept_reviewed_at' => now()],
            },
        ]);
    }

    /**
     * 销假（支部操作）。
     */
    public function cancel(string $returnDate, ?string $note = null, ?int $operatorId = null): void
    {
        $this->update([
            'status' => '已销假',
            'return_date' => $returnDate,
            'cancel_note' => $note,
        ]);
    }

    /**
     * 按组织及下级组织过滤（经党员归属组织）。
     */
    public function scopeVisibleTo(Builder $query, array $orgIds): Builder
    {
        return $query->whereHas('member', fn (Builder $memberQuery) => $memberQuery->visibleTo($orgIds));
    }
}
