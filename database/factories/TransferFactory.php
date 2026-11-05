<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\Organization;
use App\Models\Transfer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transfer>
 */
class TransferFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'direction' => '转出',
            'transfer_type' => fake()->randomElement(['校内', '省内', '跨省', '校外']),
            'from_org_id' => Organization::factory(),
            'to_org_name' => fake()->randomElement(['成都市高新区党委', '四川省直机关工委', '北京市海淀区党委']),
            'reason' => fake()->sentence(),
            'apply_at' => now(),
            'status' => '待支部审核',
        ];
    }

    /**
     * 待院级审核。
     */
    public function pendingCollege(): static
    {
        return $this->state(fn () => [
            'status' => '待院级审核',
            'branch_reviewer' => 1,
            'branch_reviewed_at' => now(),
        ]);
    }

    /**
     * 待组织部审核。
     */
    public function pendingDept(): static
    {
        return $this->state(fn () => [
            'status' => '待组织部审核',
            'branch_reviewer' => 1,
            'branch_reviewed_at' => now(),
            'college_reviewer' => 1,
            'college_reviewed_at' => now(),
        ]);
    }

    /**
     * 已办结。
     */
    public function finished(): static
    {
        return $this->state(fn () => [
            'status' => '已办结',
            'branch_reviewer' => 1,
            'branch_reviewed_at' => now(),
            'college_reviewer' => 1,
            'college_reviewed_at' => now(),
            'dept_reviewer' => 1,
            'dept_reviewed_at' => now(),
            'letter_no' => 'ZZJD-'.now()->format('Y').'-'.fake()->numerify('######'),
            'finished_at' => now(),
        ]);
    }
}
