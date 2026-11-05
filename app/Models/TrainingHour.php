<?php

namespace App\Models;

use Database\Factories\TrainingHourFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 学时流水（干部培训档案）。
 *
 * 归属主体优先 trainee_id（学员名册，含党外干部）；党员身份的学员同时回填
 * member_id。审核链路：组织部直接录入 → 已生效；协同端提交 → 待审核，
 * 经组织部审核通过后才计入正式学时（已生效）。
 */
class TrainingHour extends Model
{
    /** @use HasFactory<TrainingHourFactory> */
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = [
        'member_id', 'trainee_id', 'org_id', 'source_type', 'source_id',
        'title', 'project_name', 'training_category', 'training_form',
        'hours', 'occurred_on', 'year', 'status',
        'submitter_org_id', 'submitted_by', 'reviewer', 'reviewed_at',
        'review_note', 'remark', 'created_by',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'occurred_on' => 'date',
        'reviewed_at' => 'datetime',
    ];

    /** @var array<string, string> 学时状态 */
    public const array STATUSES = [
        '已生效' => '已生效',
        '待审核' => '待审核',
        '已驳回' => '已驳回',
    ];

    /** @var array<string, string> 学时口径 */
    public const array TRAINING_CATEGORIES = [
        '网络自学' => '网络自学',
        '集中培训' => '集中培训',
    ];

    /** @var array<string, string> 来源类型 */
    public const array SOURCE_TYPES = [
        '线下培训' => '线下培训',
        '线上课程' => '线上课程',
        '批量导入' => '批量导入',
    ];

    public function trainee(): BelongsTo
    {
        return $this->belongsTo(Trainee::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    /**
     * 按组织及下级组织过滤（学员归属组织）。
     */
    public function scopeVisibleTo(Builder $query, array $orgIds): Builder
    {
        return $query->whereIn('org_id', $orgIds);
    }
}
