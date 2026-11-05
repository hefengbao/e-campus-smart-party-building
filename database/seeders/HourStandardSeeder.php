<?php

namespace Database\Seeders;

use App\Models\HourStandard;
use Illuminate\Database\Seeder;

class HourStandardSeeder extends Seeder
{
    /**
     * 按年度初始化学时达标标准（默认 32 学时）。
     */
    public function run(): void
    {
        $year = (int) now()->format('Y');

        foreach (['默认', '教职工党员', '学生党员', '干部'] as $targetKey) {
            HourStandard::query()->updateOrCreate(
                ['year' => $year, 'target_key' => $targetKey],
                ['required_hours' => 32],
            );
        }
    }
}
