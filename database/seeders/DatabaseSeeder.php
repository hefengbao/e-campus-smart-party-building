<?php

namespace Database\Seeders;

use App\Models\CommitteeMember;
use App\Models\Config;
use App\Models\Dict;
use App\Models\Member;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->seedOrganizations();
        $this->seedMembers();
        $this->seedUsers();
        $this->seedDicts();
        $this->seedConfigs();
        $this->call(DevelopmentStepDefinitionSeeder::class);
        $this->call(HourStandardSeeder::class);
    }

    /**
     * 组织树：学校党委 → 二级党委 → 党支部。
     */
    private function seedOrganizations(): void
    {
        $school = Organization::factory()
            ->school()
            ->create(['path' => '0']);

        $colleges = collect([
            ['short_name' => '经济学院', 'full_name' => '中共西南财经大学经济学院委员会', 'code' => 'COLLEGE-ECO'],
            ['short_name' => '会计学院', 'full_name' => '中共西南财经大学会计学院委员会', 'code' => 'COLLEGE-ACC'],
            ['short_name' => '计算机与人工智能学院', 'full_name' => '中共西南财经大学计算机与人工智能学院委员会', 'code' => 'COLLEGE-CS'],
        ])->map(function (array $attrs) use ($school): Organization {
            $college = Organization::factory()
                ->college()
                ->create($attrs + ['parent_id' => $school->id]);

            $college->forceFill(['path' => $school->path.'/'.$college->id])->save();

            return $college;
        });

        $colleges->each(function (Organization $college, int $index): void {
            $branchCount = $index === 0 ? 3 : 2;

            for ($i = 1; $i <= $branchCount; $i++) {
                $branch = Organization::factory()
                    ->branch()
                    ->create([
                        'parent_id' => $college->id,
                        'short_name' => $college->short_name.$i.'支部',
                        'full_name' => '中共西南财经大学'.$college->short_name.$i.'支部委员会',
                        'code' => 'BR-'.str_pad((string) ($college->id * 10 + $i), 4, '0', STR_PAD_LEFT),
                    ]);

                $branch->forceFill(['path' => $college->path.'/'.$branch->id])->save();
            }
        });
    }

    /**
     * 每个支部若干党员（学生 + 教工 + 预备党员）；
     * 每个二级党委 1 名党员（供党委委员账号关联）。
     */
    private function seedMembers(): void
    {
        Organization::query()
            ->where('level', 2)
            ->get()
            ->each(function (Organization $college): void {
                Member::factory()
                    ->create(['org_id' => $college->id]);
            });

        Organization::query()
            ->where('level', 3)
            ->get()
            ->each(function (Organization $branch): void {
                Member::factory()
                    ->count(4)
                    ->create(['org_id' => $branch->id]);

                Member::factory()
                    ->count(1)
                    ->probationary()
                    ->create(['org_id' => $branch->id]);

                Member::factory()
                    ->count(1)
                    ->teacher()
                    ->create(['org_id' => $branch->id]);
            });
    }

    /**
     * 测试账号（密码均为 password）。
     *
     * 面板进入权限（新架构）：
     * - member 面板：默认面板，所有登录用户可进入。
     * - branch 面板：关联党员在任党支部委员会委员。
     * - college 面板：关联党员在任党委委员会委员。
     * - org 面板：组织部工作人员记录。
     * - sysadmin 面板：系统管理员记录。
     */
    private function seedUsers(): void
    {
        $college = Organization::query()->where('level', 2)->firstOrFail();
        $branch = Organization::query()->where('level', 3)->firstOrFail();

        // 系统管理员：sysadmin 面板
        User::factory()
            ->asAdmin('系统管理员')
            ->create([
                'name' => '系统管理员',
                'email' => 'super@swufe.edu.cn',
            ]);

        // 组织部工作人员：org 面板
        User::factory()
            ->asOrgStaff('组织部管理员')
            ->create([
                'name' => '组织部管理员',
                'email' => 'org@swufe.edu.cn',
            ]);

        // 党委委员（在任）：college 面板
        $collegeCommitteeMember = Member::query()
            ->whereHas('organizationLinks', fn ($query) => $query
                ->where('organization_id', $college->id)
                ->where('is_primary', true)
                ->where('status', '正常'))
            ->firstOrFail();

        $collegeUser = User::factory()
            ->forMember($collegeCommitteeMember)
            ->create([
                'name' => $collegeCommitteeMember->name,
                'email' => 'college@swufe.edu.cn',
            ]);

        $collegeCommitteeMember->forceFill(['user_id' => $collegeUser->id])->save();

        CommitteeMember::query()->create([
            'organization_id' => $college->id,
            'member_id' => $collegeCommitteeMember->id,
            'position' => '党委书记',
            'appointment_method' => '选举',
            'term_start' => now()->subYear()->toDateString(),
            'status' => '在任',
        ]);

        // 培训协同员：仅 member 面板（协同能力后续按权限裁剪）
        User::factory()
            ->create([
                'name' => '培训协同员',
                'email' => 'training@swufe.edu.cn',
            ]);

        // 党支部委员（在任）：branch 面板
        $branchCommitteeMember = Member::query()
            ->whereHas('organizationLinks', fn ($query) => $query
                ->where('organization_id', $branch->id)
                ->where('is_primary', true)
                ->where('status', '正常'))
            ->firstOrFail();

        $branchUser = User::factory()
            ->forMember($branchCommitteeMember)
            ->create([
                'name' => $branchCommitteeMember->name,
                'email' => 'branch@swufe.edu.cn',
            ]);

        $branchCommitteeMember->forceFill(['user_id' => $branchUser->id])->save();

        CommitteeMember::query()->create([
            'organization_id' => $branch->id,
            'member_id' => $branchCommitteeMember->id,
            'position' => '党支部书记',
            'appointment_method' => '选举',
            'term_start' => now()->subYear()->toDateString(),
            'status' => '在任',
        ]);

        // 普通党员：仅 member 面板（排除支部委员，避免与 branch 账号关联同一党员）
        $member = Member::query()
            ->whereHas('organizationLinks', fn ($query) => $query
                ->where('organization_id', $branch->id)
                ->where('is_primary', true)
                ->where('status', '正常'))
            ->whereKeyNot($branchCommitteeMember->id)
            ->orderBy('id')
            ->firstOrFail();

        $memberUser = User::factory()
            ->forMember($member)
            ->create([
                'name' => $member->name,
                'email' => 'member@swufe.edu.cn',
            ]);

        $member->forceFill(['user_id' => $memberUser->id])->save();
    }

    /**
     * 字典数据。
     */
    private function seedDicts(): void
    {
        $dicts = [
            'education' => ['博士', '硕士', '本科', '专科', '其他'],
            'identity' => ['正式党员', '预备党员', '入党积极分子'],
            'gender' => ['男', '女'],
            'ethnicity' => ['汉族', '回族', '藏族', '维吾尔族', '苗族', '彝族', '壮族', '其他'],
            'member_status' => ['正常', '停止党籍', '开除党籍', '死亡', '转出'],
            'data_source' => ['初始导入', '外部同步', '手工录入'],
            'committee_branch_position' => ['党支部书记', '党支部副书记', '组织委员', '宣传委员', '纪检委员', '青年委员'],
            'committee_college_position' => ['党委书记', '党委副书记', '组织委员', '宣传委员', '纪检委员'],
            'appointment_method' => ['选举', '指派'],
        ];

        foreach ($dicts as $type => $labels) {
            foreach ($labels as $index => $label) {
                Dict::query()->create([
                    'type' => $type,
                    'key' => $label,
                    'label' => $label,
                    'sort_order' => $index,
                    'enabled' => true,
                ]);
            }
        }
    }

    /**
     * 系统配置。
     */
    private function seedConfigs(): void
    {
        $configs = [
            ['key' => 'system.name', 'value' => '智慧党建系统', 'name' => '系统名称'],
            ['key' => 'system.school', 'value' => '西南财经大学', 'name' => '学校名称'],
            ['key' => 'member.id_card.mask', 'value' => '1', 'name' => '身份证脱敏显示'],
            ['key' => 'member.join_age.min', 'value' => '18', 'name' => '入党最低年龄'],
            ['key' => 'member.full_member.min_years', 'value' => '1', 'name' => '预备期最短年限'],
            // 工作坊问答敏感词（逗号分隔，兼容中文逗号；空值回退默认词库）。
            ['key' => 'workshop_sensitive_words', 'value' => '', 'name' => '工作坊敏感词', 'remark' => '逗号分隔，留空使用默认词库'],
            // 监督预警阈值。
            ['key' => 'term_warning_days', 'value' => '180', 'name' => '换届预警提前天数'],
            ['key' => 'meeting_reminder_day', 'value' => '25', 'name' => '组织生活提醒日'],
            ['key' => 'dev_step_overdue_days', 'value' => '30', 'name' => '发展步骤超期天数'],
            ['key' => 'probation_warning_days', 'value' => '30', 'name' => '预备期到期提醒天数'],
        ];

        foreach ($configs as $config) {
            Config::query()->create($config);
        }
    }
}
