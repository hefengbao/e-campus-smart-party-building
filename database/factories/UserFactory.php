<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\Member;
use App\Models\OrgStaff;
use App\Models\RoleGrant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // 主键 = 工号/学号（字符串）
            'id' => fake()->unique()->numerify('30########'),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * 指定角色授权（可选数据范围组织）。
     */
    public function withRole(string $roleCode, ?int $orgId = null): static
    {
        return $this->afterCreating(function (User $user) use ($roleCode, $orgId): void {
            RoleGrant::query()->create([
                'user_id' => $user->id,
                'role_code' => $roleCode,
                'org_id' => $orgId,
                'is_primary' => true,
                'status' => '正常',
            ]);
        });
    }

    /**
     * 关联党员档案（一一对应）。
     */
    public function forMember(Member $member): static
    {
        return $this->state(fn () => [
            'member_id' => $member->id,
        ]);
    }

    /**
     * 创建组织部工作人员档案（org panel 进入权限）。
     */
    public function asOrgStaff(?string $name = null): static
    {
        return $this->afterCreating(function (User $user) use ($name): void {
            OrgStaff::query()->create([
                'user_id' => $user->id,
                'name' => $name ?? $user->name,
                'status' => '在职',
            ]);
        });
    }

    /**
     * 创建系统管理员档案（sysadmin panel 进入权限）。
     */
    public function asAdmin(?string $name = null): static
    {
        return $this->afterCreating(function (User $user) use ($name): void {
            Admin::query()->create([
                'user_id' => $user->id,
                'name' => $name ?? $user->name,
                'status' => '启用',
            ]);
        });
    }
}
