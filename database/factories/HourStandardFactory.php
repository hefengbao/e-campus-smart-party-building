<?php

namespace Database\Factories;

use App\Models\HourStandard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HourStandard>
 */
class HourStandardFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'year' => (int) now()->format('Y'),
            'target_key' => '默认',
            'required_hours' => 32.0,
        ];
    }
}
