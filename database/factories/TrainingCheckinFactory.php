<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\Training;
use App\Models\TrainingCheckin;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingCheckin>
 */
class TrainingCheckinFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'training_id' => Training::factory(),
            'member_id' => Member::factory(),
            'checkin_at' => now(),
            'method' => fake()->randomElement(['二维码', '后台录入']),
            'status' => '出勤',
        ];
    }
}
