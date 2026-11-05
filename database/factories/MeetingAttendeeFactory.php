<?php

namespace Database\Factories;

use App\Models\Meeting;
use App\Models\MeetingAttendee;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MeetingAttendee>
 */
class MeetingAttendeeFactory extends Factory
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
            'member_id' => Member::factory(),
            'confirm_status' => '待确认',
            'attendance_status' => '未签到',
        ];
    }

    /**
     * 已出勤。
     */
    public function attended(): static
    {
        return $this->state(fn () => [
            'confirm_status' => '参加',
            'attendance_status' => '出勤',
            'sign_in_at' => now(),
            'sign_in_method' => '后台录入',
        ]);
    }

    /**
     * 请假。
     */
    public function onLeave(): static
    {
        return $this->state(fn () => [
            'confirm_status' => '请假',
            'attendance_status' => '请假',
            'leave_reason' => '因公出差',
        ]);
    }
}
