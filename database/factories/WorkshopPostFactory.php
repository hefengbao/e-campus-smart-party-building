<?php

namespace Database\Factories;

use App\Models\WorkshopPost;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkshopPost>
 */
class WorkshopPostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'content' => fake()->paragraph(),
            'tag' => fake()->randomElement(['党员发展', '组织关系', '教育培训', '换届选举', '党费管理', '其他']),
            'author' => 1,
            'author_name' => fake()->name(),
            'is_pinned' => false,
            'is_hidden' => false,
            'is_resolved' => false,
            'view_count' => 0,
        ];
    }

    /**
     * 已置顶。
     */
    public function pinned(): static
    {
        return $this->state(fn () => ['is_pinned' => true]);
    }

    /**
     * 已隐藏。
     */
    public function hidden(): static
    {
        return $this->state(fn () => ['is_hidden' => true]);
    }
}
