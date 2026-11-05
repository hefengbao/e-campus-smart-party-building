<?php

namespace Database\Factories;

use App\Models\Meeting;
use App\Models\MeetingPlan;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MeetingPlan>
 */
class MeetingPlanFactory extends Factory
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
            'year' => (int) now()->format('Y'),
            'month' => fake()->numberBetween(1, 12),
            'meeting_type' => fake()->randomElement(array_keys(Meeting::MEETING_TYPES)),
            'planned_times' => 1,
        ];
    }
}
