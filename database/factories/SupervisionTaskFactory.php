<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\SupervisionTask;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupervisionTask>
 */
class SupervisionTaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->randomElement(['完成年度党员发展计划', '提交组织生活会总结', '更新党员信息台账']),
            'content' => fake()->sentence(),
            'org_id' => Organization::factory(),
            'due_date' => fake()->dateTimeBetween('+1 day', '+30 days')->format('Y-m-d'),
            'status' => '进行中',
            'created_by' => 1,
        ];
    }

    /**
     * 已完成。
     */
    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => '已完成',
            'completed_at' => now(),
            'completion_note' => '已按要求完成',
        ]);
    }

    /**
     * 已逾期（截止日期已过）。
     */
    public function overdue(): static
    {
        return $this->state(fn () => [
            'due_date' => now()->subDays(3)->format('Y-m-d'),
        ]);
    }
}
