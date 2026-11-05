<?php

namespace Database\Factories;

use App\Models\Meeting;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Meeting>
 */
class MeetingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'org_id' => Organization::factory(),
            'meeting_type' => fake()->randomElement(array_keys(Meeting::MEETING_TYPES)),
            'title' => fake()->sentence(),
            'planned_at' => fake()->dateTimeBetween('-1 month', '+1 month'),
            'actual_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'location' => fake()->word().'会议室',
            'host' => fake()->name(),
            'recorder' => fake()->name(),
            'status' => '草稿',
            'summary' => fake()->paragraph(),
            'expected_count' => fake()->numberBetween(5, 30),
            'created_by' => null,
        ];
    }

    /**
     * 已提交状态。
     */
    public function submitted(): static
    {
        return $this->state(fn () => [
            'status' => '已提交',
            'submitted_at' => now(),
        ]);
    }

    /**
     * 已归档状态。
     */
    public function archived(): static
    {
        return $this->state(fn () => [
            'status' => '已归档',
            'submitted_at' => now()->subDays(3),
            'reviewed_at' => now(),
        ]);
    }

    /**
     * 退回修改状态。
     */
    public function returned(): static
    {
        return $this->state(fn () => [
            'status' => '退回修改',
            'submitted_at' => now()->subDays(2),
            'reviewed_at' => now(),
            'review_comment' => '材料不完整，请补充签到表。',
        ]);
    }
}
