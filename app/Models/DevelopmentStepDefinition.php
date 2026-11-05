<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 五阶段 25 步骤定义（流程配置表，种子数据预置）。
 */
class DevelopmentStepDefinition extends Model
{
    /** @var array<int, string> */
    protected $fillable = [
        'stage_no', 'stage_name', 'step_no', 'step_name',
        'default_days', 'responsible_role', 'required_materials', 'description',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'required_materials' => 'array',
    ];

    /** @var array<string, string> 办理主体 */
    public const array RESPONSIBLE_ROLES = [
        'member' => '本人',
        'branch' => '党支部',
        'college' => '院级党组织',
        'org_dept' => '党委组织部',
    ];
}
