<?php

namespace Tests\Feature;

use App\Models\CommitteeMember;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase1PanelAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function seedDb(): void
    {
        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_accesses_sysadmin_panel(): void
    {
        $this->seedDb();

        $user = User::query()->where('email', 'super@swufe.edu.cn')->firstOrFail();

        $this->assertTrue($user->canAccessPanel(Filament::getPanel('sysadmin')));
        $this->assertFalse($user->canAccessPanel(Filament::getPanel('org')));
    }

    public function test_org_staff_accesses_org_panel(): void
    {
        $this->seedDb();

        $user = User::query()->where('email', 'org@swufe.edu.cn')->firstOrFail();

        $this->assertTrue($user->canAccessPanel(Filament::getPanel('org')));
        $this->assertFalse($user->canAccessPanel(Filament::getPanel('sysadmin')));
    }

    public function test_college_committee_member_accesses_college_panel(): void
    {
        $this->seedDb();

        $user = User::query()->where('email', 'college@swufe.edu.cn')->firstOrFail();

        $this->assertTrue($user->canAccessPanel(Filament::getPanel('college')));
        $this->assertFalse($user->canAccessPanel(Filament::getPanel('org')));
    }

    public function test_branch_committee_member_accesses_branch_panel(): void
    {
        $this->seedDb();

        $user = User::query()->where('email', 'branch@swufe.edu.cn')->firstOrFail();

        $this->assertTrue($user->canAccessPanel(Filament::getPanel('branch')));
        $this->assertFalse($user->canAccessPanel(Filament::getPanel('college')));
    }

    public function test_plain_member_only_accesses_member_panel(): void
    {
        $this->seedDb();

        $user = User::query()->where('email', 'member@swufe.edu.cn')->firstOrFail();

        $this->assertTrue($user->canAccessPanel(Filament::getPanel('member')));
        $this->assertFalse($user->canAccessPanel(Filament::getPanel('branch')));
        $this->assertFalse($user->canAccessPanel(Filament::getPanel('college')));
        $this->assertFalse($user->canAccessPanel(Filament::getPanel('org')));
        $this->assertFalse($user->canAccessPanel(Filament::getPanel('sysadmin')));
    }

    public function test_member_panel_is_default_for_every_authenticated_user(): void
    {
        $this->seedDb();

        // 所有登录用户（含系统管理员、组织部工作人员）均可进入 member 面板
        foreach (User::all() as $user) {
            $this->assertTrue($user->canAccessPanel(Filament::getPanel('member')));
        }

        $this->assertTrue(Filament::getPanel('member')->isDefault());
    }

    public function test_retired_committee_member_loses_panel_access(): void
    {
        $this->seedDb();

        $user = User::query()->where('email', 'branch@swufe.edu.cn')->firstOrFail();
        $this->assertTrue($user->canAccessPanel(Filament::getPanel('branch')));

        // 委员离任后失去 branch 面板权限
        CommitteeMember::query()
            ->where('member_id', $user->member_id)
            ->where('status', '在任')
            ->update(['status' => '离任']);

        $this->assertFalse($user->canAccessPanel(Filament::getPanel('branch')));
    }

    public function test_expired_term_loses_panel_access(): void
    {
        $this->seedDb();

        $user = User::query()->where('email', 'college@swufe.edu.cn')->firstOrFail();
        $this->assertTrue($user->canAccessPanel(Filament::getPanel('college')));

        // 任期到期后失去 college 面板权限
        CommitteeMember::query()
            ->where('member_id', $user->member_id)
            ->where('status', '在任')
            ->update(['term_end' => now()->subDay()->toDateString()]);

        $this->assertFalse($user->canAccessPanel(Filament::getPanel('college')));
    }

    public function test_user_without_member_cannot_enter_committee_panels(): void
    {
        $this->seedDb();

        // 培训协同员未关联党员档案，不能进入支部/分党委面板
        $user = User::query()->where('email', 'training@swufe.edu.cn')->firstOrFail();

        $this->assertNull($user->member_id);
        $this->assertFalse($user->canAccessPanel(Filament::getPanel('branch')));
        $this->assertFalse($user->canAccessPanel(Filament::getPanel('college')));
    }

    public function test_departed_org_staff_loses_org_panel_access(): void
    {
        $this->seedDb();

        $user = User::query()->where('email', 'org@swufe.edu.cn')->firstOrFail();
        $this->assertTrue($user->canAccessPanel(Filament::getPanel('org')));

        $user->orgStaff()->update(['status' => '离职']);

        $this->assertFalse($user->canAccessPanel(Filament::getPanel('org')));
    }

    public function test_guest_redirected_from_panel(): void
    {
        // 未登录访问面板首页应跳转到登录页；登录页本身返回 200
        $this->get('/org')->assertRedirect('/org/login');
        $this->get('/org/login')->assertSuccessful();
    }

    public function test_college_data_scope_contains_descendants_only(): void
    {
        $this->seedDb();

        $user = User::query()->where('email', 'college@swufe.edu.cn')->firstOrFail();
        $orgIds = $user->scopedOrgIds();

        $this->assertNotEmpty($orgIds);

        $college = Organization::query()->where('level', 2)->firstOrFail();

        $this->assertContains($college->id, $orgIds);

        $branchIds = Organization::query()
            ->where('parent_id', $college->id)
            ->pluck('id')
            ->all();

        foreach ($branchIds as $branchId) {
            $this->assertContains($branchId, $orgIds);
        }

        $otherCollege = Organization::query()
            ->where('level', 2)
            ->whereKeyNot($college->id)
            ->first();

        if ($otherCollege !== null) {
            $this->assertNotContains($otherCollege->id, $orgIds);
        }
    }

    public function test_committee_member_scope_falls_back_to_own_organization(): void
    {
        $this->seedDb();

        // 新架构下 college 用户无角色授权，数据范围回退到其委员所在组织及下级
        $user = User::query()->where('email', 'college@swufe.edu.cn')->firstOrFail();

        $committee = CommitteeMember::query()
            ->where('member_id', $user->member_id)
            ->where('status', '在任')
            ->firstOrFail();

        $orgIds = $user->scopedOrgIds();

        $this->assertContains($committee->organization_id, $orgIds);
    }
}
