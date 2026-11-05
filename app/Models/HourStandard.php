<?php

namespace App\Models;

use Database\Factories\HourStandardFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 年度学时标准（按身份/类别）。
 */
class HourStandard extends Model
{
    /** @use HasFactory<HourStandardFactory> */
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = ['year', 'target_key', 'required_hours', 'note'];

    /** @var array<string, string> 考核对象 */
    public const array TARGET_KEYS = [
        '默认' => '默认',
        '教职工党员' => '教职工党员',
        '学生党员' => '学生党员',
        '干部' => '干部',
    ];

    /**
     * 取指定年度某对象的标准学时，未配置时回退默认。
     */
    public static function requiredHoursFor(int $year, string $targetKey = '默认'): float
    {
        $standard = static::query()
            ->where('year', $year)
            ->where('target_key', $targetKey)
            ->first()
            ?? static::query()->where('year', $year)->where('target_key', '默认')->first();

        return (float) ($standard->required_hours ?? 0);
    }
}
