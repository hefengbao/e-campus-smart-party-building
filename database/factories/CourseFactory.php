<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->randomElement(['党史专题', '党章解读', '二十大精神学习', '党务工作实务']).fake()->numerify(' 第##讲'),
            'category' => fake()->randomElement(['党课', '党史教育', '理论解读', '业务能力', '时政']),
            'provider_level' => '校级',
            'org_id' => Organization::factory(),
            'hours' => fake()->randomElement([2.0, 4.0, 6.0, 8.0]),
            'description' => fake()->sentence(),
            'tags' => [fake()->randomElement(['必修', '选修'])],
            'publish' => true,
            'view_count' => fake()->numberBetween(0, 500),
        ];
    }
}
