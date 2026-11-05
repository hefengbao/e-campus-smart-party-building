<?php

namespace App\Models;

use Database\Factories\TraineeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 干部培训学员名册（独立于党员库，党外干部可纳入）。
 *
 * 学员与党员信息库解耦：党员身份的学员通过 member_id 关联党员档案，
 * 党外干部 member_id 为空。协同端无权查看名册，仅可按工号精确查单人。
 */
class Trainee extends Model
{
    /** @use HasFactory<TraineeFactory> */
    use HasFactory;

    use SoftDeletes;

    /** @var array<int, string> */
    protected $fillable = [
        'staff_no', 'name', 'gender', 'birth_date', 'phone',
        'org_id', 'org_name', 'department', 'position',
        'cadre_level', 'cadre_type', 'political_status',
        'is_party_member', 'member_id', 'status', 'enabled',
        'data_source', 'remark',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'birth_date' => 'date',
        'is_party_member' => 'boolean',
        'enabled' => 'boolean',
    ];

    /** @var array<string, string> 学员状态 */
    public const array STATUSES = [
        '在训' => '在训',
        '结业' => '结业',
        '调离' => '调离',
        '退休' => '退休',
    ];

    /** @var array<string, string> 干部类型 */
    public const array CADRE_TYPES = [
        '党政管理干部' => '党政管理干部',
        '专业技术干部' => '专业技术干部',
        '党外干部' => '党外干部',
        '其他' => '其他',
    ];

    /** @var array<string, string> 政治面貌 */
    public const array POLITICAL_STATUSES = [
        '中共党员' => '中共党员',
        '中共预备党员' => '中共预备党员',
        '民主党派' => '民主党派',
        '无党派人士' => '无党派人士',
        '群众' => '群众',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function hours(): HasMany
    {
        return $this->hasMany(TrainingHour::class);
    }

    /**
     * 生效学时合计。
     */
    public function effectiveHours(?int $year = null): float
    {
        return (float) $this->hours()
            ->where('status', '已生效')
            ->when($year !== null, fn (Builder $q) => $q->where('year', $year))
            ->sum('hours');
    }

    /**
     * 按组织及下级组织过滤（数据范围收敛）。
     */
    public function scopeVisibleTo(Builder $query, array $orgIds): Builder
    {
        return $query->where(function (Builder $q) use ($orgIds) {
            $q->whereIn('org_id', $orgIds)
                ->orWhereNull('org_id');
        });
    }
}
