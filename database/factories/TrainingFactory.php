<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Training;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Training>
 */
class TrainingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-1 month', '+1 month');

        return [
            'title' => fake()->randomElement(['党支部书记培训班', '发展对象培训班', '党员骨干培训班', '新党员培训']).fake()->numerify('（第##期）'),
            'category' => fake()->randomElement(['党员教育', '干部培训', '发展对象培训']),
            'org_id' => Organization::factory(),
            'provider_level' => '校级',
            'hours_standard' => fake()->randomElement([8.0, 16.0, 24.0]),
            'start_date' => $start->format('Y-m-d'),
            'end_date' => fake()->dateTimeBetween($start, '+2 months')->format('Y-m-d'),
            'signup_deadline' => fake()->dateTimeBetween('-2 weeks', '+1 week')->format('Y-m-d'),
            'need_signup' => true,
            'need_approval' => true,
            'status' => '报名中',
            'description' => fake()->sentence(),
        ];
    }

    /**
     * 进行中（可签到）。
     */
    public function ongoing(): static
    {
        return $this->state(fn () => ['status' => '进行中']);
    }

    /**
     * 已结束。
     */
    public function ended(): static
    {
        return $this->state(fn () => ['status' => '已结束']);
    }
}
