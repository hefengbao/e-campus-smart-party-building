<?php

namespace Database\Factories;

use App\Models\CommitteeMember;
use App\Models\Member;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommitteeMember>
 */
class CommitteeMemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'member_id' => Member::factory(),
            'position' => '委员',
            'appointment_method' => fake()->randomElement(['选举', '指派']),
            'term_start' => fake()->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'term_end' => null,
            'status' => '在任',
            'sort_order' => 0,
        ];
    }

    /**
     * 党支部委员会职务。
     */
    public function branchPosition(string $position = '党支部书记'): static
    {
        return $this->state(fn () => ['position' => $position]);
    }

    /**
     * 党委委员会职务。
     */
    public function collegePosition(string $position = '党委书记'): static
    {
        return $this->state(fn () => ['position' => $position]);
    }

    /**
     * 离任。
     */
    public function retired(): static
    {
        return $this->state(fn () => [
            'status' => '离任',
            'term_end' => fake()->dateTimeBetween('-1 year', '-1 month')->format('Y-m-d'),
        ]);
    }

    /**
     * 指派任命。
     */
    public function appointed(): static
    {
        return $this->state(fn () => ['appointment_method' => '指派']);
    }
}
