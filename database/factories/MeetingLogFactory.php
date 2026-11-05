<?php

namespace Database\Factories;

use App\Models\Meeting;
use App\Models\MeetingLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MeetingLog>
 */
class MeetingLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'meeting_id' => Meeting::factory(),
            'action' => '提交',
            'operator' => null,
            'role' => '支部',
            'comment' => null,
            'created_at' => now(),
        ];
    }

    /**
     * 归档动作。
     */
    public function archive(User $user): static
    {
        return $this->state(fn () => [
            'action' => '归档',
            'operator' => $user->id,
            'role' => '院级',
        ]);
    }
}
