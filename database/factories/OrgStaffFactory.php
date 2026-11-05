<?php

namespace Database\Factories;

use App\Models\OrgStaff;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrgStaff>
 */
class OrgStaffFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->name(),
            'position' => '组织部干部',
            'phone' => '13'.fake()->numerify('#########'),
            'status' => '在职',
        ];
    }

    /**
     * 离职。
     */
    public function departed(): static
    {
        return $this->state(fn () => ['status' => '离职']);
    }
}
