<?php

namespace Database\Factories;

use App\Models\Meeting;
use App\Models\MeetingAttachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MeetingAttachment>
 */
class MeetingAttachmentFactory extends Factory
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
            'kind' => '纪要',
            'name' => fake()->word().'.pdf',
            'file_path' => 'meetings/'.fake()->uuid().'.pdf',
            'uploaded_by' => null,
        ];
    }

    /**
     * 上传人。
     */
    public function by(User $user): static
    {
        return $this->state(fn () => ['uploaded_by' => $user->id]);
    }
}
