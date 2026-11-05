<?php

namespace Database\Factories;

use App\Models\WorkshopLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkshopLink>
 */
class WorkshopLinkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->randomElement(['发展党员流程图解', '党费计算器', '组织关系转接指南']),
            'url' => fake()->url(),
            'category' => fake()->randomElement(['常用工具', '学习资料', '办事指南', '其他']),
            'sort' => fake()->numberBetween(0, 100),
            'is_pinned' => false,
            'click_count' => 0,
            'enabled' => true,
            'created_by' => 1,
        ];
    }

    /**
     * 置顶。
     */
    public function pinned(): static
    {
        return $this->state(fn () => ['is_pinned' => true]);
    }
}
