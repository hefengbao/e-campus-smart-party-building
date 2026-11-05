<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Trainee;
use App\Models\TrainingHour;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingHour>
 */
class TrainingHourFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = (int) now()->format('Y');

        return [
            'trainee_id' => Trainee::factory(),
            'org_id' => Organization::factory(),
            'source_type' => '线下培训',
            'title' => fake()->randomElement(['党支部书记培训', '党员集中轮训', '网络党课学习']),
            'training_category' => fake()->randomElement(['网络自学', '集中培训']),
            'training_form' => fake()->randomElement(['学校学习平台', '其他网络平台', '脱产培训']),
            'hours' => fake()->randomElement([2.0, 4.0, 8.0, 16.0]),
            'occurred_on' => fake()->dateTimeBetween("$year-01-01", 'now')->format('Y-m-d'),
            'year' => $year,
            'status' => '已生效',
        ];
    }

    /**
     * 协同端提交（待审核）。
     */
    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => '待审核',
            'submitted_by' => 1,
            'submitter_org_id' => Organization::factory(),
        ]);
    }

    /**
     * 已驳回。
     */
    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => '已驳回',
            'review_note' => '材料不全，请补充。',
        ]);
    }
}
