<?php

namespace Database\Factories;

use App\Models\DevelopmentCase;
use App\Models\DevelopmentStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DevelopmentStep>
 */
class DevelopmentStepFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'case_id' => DevelopmentCase::factory(),
            'stage_no' => 1,
            'step_no' => 1,
            'step_name' => '递交入党申请书',
            'status' => '未开始',
        ];
    }

    /**
     * 已完成。
     */
    public function done(): static
    {
        return $this->state(fn () => [
            'status' => '已完成',
            'done_date' => now()->toDateString(),
        ]);
    }
}
