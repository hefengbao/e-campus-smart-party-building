<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'ORG'.fake()->unique()->numerify('#####'),
            'short_name' => '党支部'.fake()->unique()->numerify('##'),
            'full_name' => '中共西南财经大学'.$this->faker->word.'委员会',
            'level' => 3,
            'status' => '正常',
            'sort_order' => 0,
        ];
    }

    /**
     * 学校层组织。
     */
    public function school(): static
    {
        return $this->state(fn () => [
            'level' => 1,
            'short_name' => '学校党委',
            'full_name' => '中共西南财经大学委员会',
            'code' => 'SWUFE',
        ]);
    }

    /**
     * 院级组织。
     */
    public function college(): static
    {
        return $this->state(fn () => [
            'level' => 2,
            'short_name' => fake()->unique()->word.'学院',
            'full_name' => '中共西南财经大学'.fake()->word.'学院委员会',
        ]);
    }

    /**
     * 党支部层组织。
     */
    public function branch(): static
    {
        return $this->state(fn () => [
            'level' => 3,
            'member_type' => 'stu',
            'nature' => '学生党支部',
        ]);
    }

    /**
     * 配置 path（需在 parent 保存后调用）。
     */
    public function withPath(): static
    {
        return $this->afterCreating(function (Organization $org): void {
            $path = $org->parent ? rtrim($org->parent->path, '/').'/'.$org->id : (string) $org->id;
            $org->forceFill(['path' => $path])->save();
        });
    }
}
