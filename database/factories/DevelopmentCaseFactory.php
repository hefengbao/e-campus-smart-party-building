<?php

namespace Database\Factories;

use App\Models\DevelopmentCase;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DevelopmentCase>
 */
class DevelopmentCaseFactory extends Factory
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
            'stage_no' => 1,
            'step_no' => 1,
            'status' => '进行中',
            'apply_date' => fake()->dateTimeBetween('-2 years', '-6 months')->format('Y-m-d'),
            'started_at' => now(),
        ];
    }

    /**
     * 已完成。
     */
    public function finished(): static
    {
        return $this->state(fn () => [
            'status' => '已完成',
            'stage_no' => 5,
            'step_no' => 25,
            'finished_at' => now(),
        ]);
    }

    /**
     * 已终止。
     */
    public function terminated(): static
    {
        return $this->state(fn () => [
            'status' => '已终止',
            'finished_at' => now(),
            'remark' => '个人原因终止发展流程。',
        ]);
    }
}
