<?php

namespace Database\Factories;

use App\Models\WorkshopPost;
use App\Models\WorkshopReply;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkshopReply>
 */
class WorkshopReplyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'post_id' => WorkshopPost::factory(),
            'content' => fake()->paragraph(),
            'author' => 1,
            'author_name' => fake()->name(),
            'is_official' => false,
            'is_best' => false,
            'is_hidden' => false,
        ];
    }

    /**
     * 官方回复（组织部）。
     */
    public function official(): static
    {
        return $this->state(fn () => ['is_official' => true]);
    }

    /**
     * 最佳答案。
     */
    public function best(): static
    {
        return $this->state(fn () => ['is_best' => true]);
    }
}
