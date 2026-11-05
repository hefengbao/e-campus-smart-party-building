<?php

namespace Database\Factories;

use App\Models\FeeRecord;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeeRecord>
 */
class FeeRecordFactory extends Factory
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
            'period' => now()->format('Y-m'),
            'amount' => fake()->randomFloat(2, 0.5, 20),
            'pay_status' => '已交纳',
            'pay_date' => fake()->dateTimeThisMonth()->format('Y-m-d'),
            'source' => '党费系统同步',
        ];
    }

    /**
     * 未交纳。
     */
    public function unpaid(): static
    {
        return $this->state(fn () => [
            'pay_status' => '未交纳',
            'pay_date' => null,
        ]);
    }
}
