<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Config extends Model
{
    /** @var array<int, string> */
    protected $fillable = ['key', 'value', 'name', 'remark'];

    /**
     * 读取参数值，未配置时返回默认值。
     */
    public static function value(string $key, ?string $default = null): ?string
    {
        return static::query()->where('key', $key)->value('value') ?? $default;
    }
}
