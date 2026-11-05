<?php

namespace Database\Factories;

use App\Models\Alert;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Alert>
 */
class AlertFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $rule = fake()->randomElement(array_keys(Alert::RULES));

        return [
            'rule' => $rule,
            'level' => fake()->randomElement(['提示', '警告', '严重']),
            'title' => $rule.'：'.fake()->word(),
            'content' => fake()->sentence(),
            'org_id' => Organization::factory(),
            'member_id' => null,
            'biz_type' => null,
            'biz_id' => null,
            'dedup_key' => 'test:'.fake()->unique()->uuid(),
            'status' => '未处置',
        ];
    }

    /**
     * 已处置。
     */
    public function handled(): static
    {
        return $this->state(fn () => [
            'status' => '已处置',
            'handled_by' => 1,
            'handled_at' => now(),
            'handle_note' => '已线下核实处理',
        ]);
    }

    /**
     * 已忽略。
     */
    public function ignored(): static
    {
        return $this->state(fn () => [
            'status' => '已忽略',
            'handled_by' => 1,
            'handled_at' => now(),
        ]);
    }
}
