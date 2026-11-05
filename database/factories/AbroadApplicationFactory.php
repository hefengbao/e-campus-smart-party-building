<?php

namespace Database\Factories;

use App\Models\AbroadApplication;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AbroadApplication>
 */
class AbroadApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-1 month', '+1 month');
        $end = fake()->dateTimeBetween($start, '+3 months');
        $days = (int) ($start->diff($end)->days + 1);

        return [
            'member_id' => Member::factory(),
            'destination' => fake()->randomElement(['美国', '英国', '日本', '新加坡', '中国香港']),
            'reason' => fake()->randomElement(['学术交流', '探亲', '旅游', '访学']),
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'duration_days' => $days,
            'approval_route' => $days >= AbroadApplication::SIX_MONTH_DAYS ? '6个月及以上' : '6个月以下',
            'retain_relation' => true,
            'contact_phone' => '13'.fake()->numerify('#########'),
            'status' => '待支部审核',
        ];
    }

    /**
     * 6 个月及以上（需组织部终审）。
     */
    public function longTerm(): static
    {
        return $this->state(fn () => [
            'start_date' => now()->addDays(7)->format('Y-m-d'),
            'end_date' => now()->addDays(200)->format('Y-m-d'),
            'duration_days' => 194,
            'approval_route' => '6个月及以上',
        ]);
    }

    /**
     * 已批准。
     */
    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => '已批准',
            'branch_reviewer' => 1,
            'branch_reviewed_at' => now(),
            'college_reviewer' => 1,
            'college_reviewed_at' => now(),
        ]);
    }
}
