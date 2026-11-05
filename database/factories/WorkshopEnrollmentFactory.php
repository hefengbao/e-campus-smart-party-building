<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\WorkshopActivity;
use App\Models\WorkshopEnrollment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkshopEnrollment>
 */
class WorkshopEnrollmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'activity_id' => WorkshopActivity::factory(),
            'org_id' => Organization::factory(),
            'contact_user' => 1,
            'headcount' => fake()->numberBetween(1, 5),
            'status' => '待审核',
            'remark' => fake()->sentence(),
        ];
    }

    /**
     * 已通过。
     */
    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => '已通过',
            'reviewed_by' => 1,
            'reviewed_at' => now(),
        ]);
    }

    /**
     * 已驳回。
     */
    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => '已驳回',
            'reviewed_by' => 1,
            'reviewed_at' => now(),
        ]);
    }
}
