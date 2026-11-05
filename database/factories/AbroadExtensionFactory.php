<?php

namespace Database\Factories;

use App\Models\AbroadApplication;
use App\Models\AbroadExtension;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AbroadExtension>
 */
class AbroadExtensionFactory extends Factory
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
            'extend_months' => 6,
            'reason' => '因访学项目延长，需继续保留组织关系。',
            'status' => '待审批',
            'applied_at' => now(),
        ];
    }
}
