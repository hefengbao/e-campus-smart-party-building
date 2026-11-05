<?php

namespace Database\Factories;

use App\Models\AbroadApplication;
use App\Models\AbroadContactLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AbroadContactLog>
 */
class AbroadContactLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'abroad_application_id' => AbroadApplication::factory(),
            'contact_date' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'method' => fake()->randomElement(['电话', '邮件', '视频']),
            'content' => '已与本人取得联系，思想状况稳定。',
            'operator' => 1,
            'created_at' => now(),
        ];
    }
}
