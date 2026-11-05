<?php

namespace Database\Factories;

use App\Models\DevelopmentStepDefinition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DevelopmentStepDefinition>
 */
class DevelopmentStepDefinitionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'stage_no' => 1,
            'stage_name' => '入党申请',
            'step_no' => fake()->unique()->numberBetween(1, 25),
            'step_name' => '递交入党申请书',
            'default_days' => 30,
            'responsible_role' => 'branch',
            'required_materials' => [],
        ];
    }
}
