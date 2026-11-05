<?php

namespace App\Models;

use Database\Factories\TrainingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 培训项目（校级/院级/支部三级发布）。
 *
 * 报名需两级审批（支部→院级）；签到使用动态签到码。
 */
class Training extends Model
{
    /** @use HasFactory<TrainingFactory> */
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = [
        'title', 'category', 'org_id', 'provider_level', 'hours_standard',
        'target_scope', 'start_date', 'end_date', 'signup_deadline',
        'need_signup', 'need_approval', 'status', 'checkin_code',
        'checkin_code_expires_at', 'description', 'created_by',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'target_scope' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'signup_deadline' => 'date',
        'need_signup' => 'boolean',
        'need_approval' => 'boolean',
        'checkin_code_expires_at' => 'datetime',
    ];

    /** @var array<string, string> 培训状态 */
    public const array STATUSES = [
        '草稿' => '草稿',
        '报名中' => '报名中',
        '进行中' => '进行中',
        '已结束' => '已结束',
    ];

    /** @var array<string, string> 培训分类 */
    public const array CATEGORIES = [
        '党员教育' => '党员教育',
        '干部培训' => '干部培训',
        '发展对象培训' => '发展对象培训',
    ];

    /** @var array<string, string> 发布层级 */
    public const array PROVIDER_LEVELS = [
        '校级' => '校级',
        '院级' => '院级',
        '支部' => '支部',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(TrainingEnrollment::class);
    }

    public function checkins(): HasMany
    {
        return $this->hasMany(TrainingCheckin::class);
    }

    /**
     * 生成动态签到码（6 位数字，30 分钟有效）。
     */
    public function refreshCheckinCode(): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->update([
            'checkin_code' => $code,
            'checkin_code_expires_at' => now()->addMinutes(30),
        ]);

        return $code;
    }

    /**
     * 签到码是否有效。
     */
    public function isCheckinCodeValid(?string $code): bool
    {
        return $code !== null
            && $this->checkin_code !== null
            && $this->checkin_code === $code
            && $this->checkin_code_expires_at !== null
            && $this->checkin_code_expires_at->isFuture();
    }

    /**
     * 按组织及下级组织过滤（数据范围收敛）。
     */
    public function scopeVisibleTo(Builder $query, array $orgIds): Builder
    {
        return $query->whereIn('org_id', $orgIds);
    }
}
