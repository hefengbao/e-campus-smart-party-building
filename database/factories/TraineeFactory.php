<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Trainee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trainee>
 */
class TraineeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isPartyMember = fake()->boolean(70);

        return [
            'staff_no' => fake()->unique()->numerify('20########'),
            'name' => fake()->name(),
            'gender' => fake()->randomElement(['男', '女']),
            'birth_date' => fake()->dateTimeBetween('-60 years', '-25 years')->format('Y-m-d'),
            'phone' => '13'.fake()->numerify('#########'),
            'org_id' => Organization::factory(),
            'department' => fake()->randomElement(['党委组织部', '经济学院', '会计学院', '工商管理学院']),
            'position' => fake()->randomElement(['科员', '副科长', '科长', '讲师', '副教授']),
            'cadre_level' => fake()->randomElement(['科级', '一般干部', '中层副职']),
            'cadre_type' => '党政管理干部',
            'political_status' => $isPartyMember ? '中共党员' : '群众',
            'is_party_member' => $isPartyMember,
            'status' => '在训',
            'enabled' => true,
            'data_source' => '手工录入',
        ];
    }

    /**
     * 党外干部（不关联党员）。
     */
    public function nonParty(): static
    {
        return $this->state(fn () => [
            'political_status' => '群众',
            'is_party_member' => false,
            'member_id' => null,
        ]);
    }

    /**
     * 已结业。
     */
    public function graduated(): static
    {
        return $this->state(fn () => ['status' => '结业']);
    }
}
