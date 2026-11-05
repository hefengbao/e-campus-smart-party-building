<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\WorkshopActivity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkshopActivity>
 */
class WorkshopActivityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('+3 days', '+10 days');

        return [
            'title' => fake()->randomElement(['党务干部能力提升工作坊', '支部书记经验交流会', '发展党员实务培训']),
            'category' => fake()->randomElement(['党务培训', '经验交流', '技能竞赛', '其他']),
            'org_id' => Organization::factory(),
            'description' => fake()->sentence(),
            'location' => fake()->word().'会议室',
            'start_at' => $start,
            'end_at' => (clone $start)->modify('+2 hours'),
            'enroll_deadline' => (clone $start)->modify('-1 day'),
            'is_public' => true,
            'cross_college' => true,
            'max_enrollments' => 0,
            'status' => '草稿',
            'created_by' => 1,
        ];
    }

    /**
     * 已发布（审核通过）。
     */
    public function published(): static
    {
        return $this->state(fn () => [
            'status' => '已发布',
            'reviewed_by' => 1,
            'reviewed_at' => now(),
            'published_at' => now(),
        ]);
    }

    /**
     * 待审核。
     */
    public function pendingReview(): static
    {
        return $this->state(fn () => [
            'status' => '待审核',
        ]);
    }

    /**
     * 已驳回。
     */
    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => '已驳回',
            'reject_reason' => '活动说明不完整，请补充后重新提交',
        ]);
    }

    /**
     * 已结束（活动时间已过）。
     */
    public function ended(): static
    {
        return $this->state(fn () => [
            'status' => '已发布',
            'reviewed_by' => 1,
            'reviewed_at' => now()->subDays(10),
            'published_at' => now()->subDays(10),
            'start_at' => now()->subDays(8),
            'end_at' => now()->subDays(8)->modify('+2 hours'),
            'enroll_deadline' => now()->subDays(9),
        ]);
    }
}
