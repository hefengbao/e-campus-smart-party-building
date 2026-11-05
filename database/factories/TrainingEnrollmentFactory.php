<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\Training;
use App\Models\TrainingEnrollment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingEnrollment>
 */
class TrainingEnrollmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'training_id' => Training::factory(),
            'member_id' => Member::factory(),
            'status' => '待支部审核',
            'applied_at' => now(),
        ];
    }

    /**
     * 待院级审核（支部已通过）。
     */
    public function pendingCollege(): static
    {
        return $this->state(fn () => [
            'status' => '待院级审核',
            'branch_reviewer' => 1,
            'branch_reviewed_at' => now(),
        ]);
    }

    /**
     * 已通过。
     */
    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => '已通过',
            'branch_reviewer' => 1,
            'branch_reviewed_at' => now(),
            'college_reviewer' => 1,
            'college_reviewed_at' => now(),
        ]);
    }
}
