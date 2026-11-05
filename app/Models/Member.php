<?php

namespace App\Models;

use Database\Factories\MemberFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    /** @use HasFactory<MemberFactory> */
    use HasFactory, SoftDeletes;

    /** 主键为工号/学号（字符串）。 */
    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    /** @var array<int, string> */
    protected $fillable = [
        'name', 'gender', 'ethnicity', 'birth_date', 'id_card', 'staff_no',
        'user_id', 'org_id', 'parent_org_id', 'relation_in_school', 'join_party_date',
        'full_member_date', 'identity', 'education', 'degree', 'native_place',
        'source_place', 'cultivation_level', 'grade', 'arrive_school_date',
        'professional_title', 'professional_title_level', 'phone', 'work_unit',
        'status', 'member_category', 'special_note', 'data_source',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'birth_date' => 'date',
        'join_party_date' => 'date',
        'full_member_date' => 'date',
        'arrive_school_date' => 'date',
        'id_card' => 'encrypted',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function parentOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'parent_org_id');
    }

    public function phoneChangeRequests(): HasMany
    {
        return $this->hasMany(PhoneChangeRequest::class);
    }

    /**
     * 党员发展案件（一一对应，外键 development_cases.member_id）。
     */
    public function developmentCase(): HasOne
    {
        return $this->hasOne(DevelopmentCase::class);
    }

    /**
     * 关联账号（一一对应，外键 members.user_id）。
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    /**
     * 身份证脱敏展示（保留前 4 后 4）。
     */
    public function getMaskedIdCardAttribute(): string
    {
        $idCard = $this->id_card;

        if ($idCard === null || $idCard === '') {
            return '—';
        }

        return substr($idCard, 0, 4).'***********'.substr($idCard, -4);
    }

    /**
     * 应用层身份证唯一性校验。
     *
     * id_card 使用 encrypted cast，同一明文每次加密产生不同密文，
     * 数据库唯一约束与 where 等值查询均无法命中，因此必须解密比对。
     *
     * @param  string  $plainIdCard  明文身份证号
     * @param  int|null  $ignoreId  编辑时忽略自身记录 ID
     */
    public static function idCardExists(string $plainIdCard, ?int $ignoreId = null): bool
    {
        return static::query()
            ->when($ignoreId !== null, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->get(['id', 'id_card'])
            ->contains(fn (self $member): bool => $member->id_card === $plainIdCard);
    }

    /**
     * 校验：入党时间 − 出生日期 ≥ 18 年。
     */
    public function passesJoinAgeCheck(): bool
    {
        if ($this->birth_date === null || $this->join_party_date === null) {
            return true;
        }

        return $this->birth_date->diffInYears($this->join_party_date) >= 18;
    }

    /**
     * 校验：转正时间 − 入党时间 ≥ 1 年。
     */
    public function passesFullMemberCheck(): bool
    {
        if ($this->join_party_date === null || $this->full_member_date === null) {
            return true;
        }

        return $this->join_party_date->diffInYears($this->full_member_date) >= 1;
    }

    /**
     * 校验不满足时是否已填写特殊备注。
     */
    public function validationSatisfied(): bool
    {
        if (! $this->passesJoinAgeCheck() || ! $this->passesFullMemberCheck()) {
            return filled($this->special_note);
        }

        return true;
    }

    /**
     * 数据范围：按角色授权的组织收敛可见性。
     *
     * @param  array<int, int>  $orgIds  可见组织 ID（含下级），空数组表示全校
     */
    public function scopeVisibleTo(Builder $query, array $orgIds): Builder
    {
        if ($orgIds === []) {
            return $query;
        }

        return $query->whereIn('org_id', $orgIds);
    }
}
