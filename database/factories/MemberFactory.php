<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    public function configure(): static
    {
        return $this->afterCreating(function (Member $member): void {
            $organizationId = $member->org_id;

            if ($organizationId !== null && ! $member->primaryOrganizationLink()->exists()) {
                $member->setPrimaryOrganization($organizationId);
            }
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $birthDate = fake()->dateTimeBetween('-60 years', '-20 years');
        $joinDate = fake()->dateTimeBetween('-10 years', 'now');

        return [
            // 主键 = 工号/学号（字符串），与 staff_no 一致
            'id' => fake()->unique()->numerify('20########'),
            'name' => fake()->name(),
            'gender' => fake()->randomElement(['男', '女']),
            'ethnicity' => '汉族',
            'birth_date' => $birthDate->format('Y-m-d'),
            'id_card' => '5101'.fake()->numerify('##############'),
            'staff_no' => fake()->unique()->numerify('20########'),
            'org_id' => Organization::factory(),
            'relation_in_school' => '学生',
            'join_party_date' => $joinDate->format('Y-m-d'),
            'identity' => '正式党员',
            'education' => '本科',
            'phone' => '13'.fake()->numerify('#########'),
            'status' => '正常',
            'data_source' => '初始导入',
        ];
    }

    /**
     * 预备党员。
     */
    public function probationary(): static
    {
        return $this->state(fn () => [
            'identity' => '预备党员',
            'full_member_date' => null,
        ]);
    }

    /**
     * 教工党员。
     */
    public function teacher(): static
    {
        return $this->state(fn () => [
            'relation_in_school' => '教工',
            'identity' => '正式党员',
            'full_member_date' => fake()->dateTimeBetween('-9 years', '-1 year')->format('Y-m-d'),
        ]);
    }
}
