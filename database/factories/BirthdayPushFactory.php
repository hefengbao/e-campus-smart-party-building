<?php

namespace Database\Factories;

use App\Models\BirthdayPush;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BirthdayPush>
 */
class BirthdayPushFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = fake()->dateTimeBetween('-1 day', '+7 days')->format('Y-m-d');

        return [
            'member_id' => Member::factory(),
            'birthday_date' => $date,
            'years' => fake()->numberBetween(1, 30),
            'channel' => '站内',
            'content' => '今天是您的第 '.fake()->numberBetween(1, 30).' 个政治生日，感谢您为党的事业作出的贡献！',
            'status' => '待推送',
        ];
    }

    /**
     * 已推送。
     */
    public function pushed(): static
    {
        return $this->state(fn () => [
            'status' => '已推送',
            'pushed_at' => now(),
        ]);
    }
}
