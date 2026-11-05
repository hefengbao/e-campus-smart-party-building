<?php

namespace Tests\Feature;

use App\Filament\Branch\Widgets\BranchOverviewWidget;
use App\Filament\College\Widgets\CollegeOverviewWidget;
use App\Filament\Member\Widgets\MemberOverviewWidget;
use App\Filament\Org\Widgets\OrgOverviewWidget;
use App\Filament\SysAdmin\Resources\AuditLogs\AuditLogResource;
use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\CommitteeMember;
use App\Models\Config;
use App\Models\Dict;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class Phase5CockpitAndAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function seedDb(): void
    {
        $this->seed(DatabaseSeeder::class);
    }

    protected function superUser(): User
    {
        return User::query()->where('email', 'super@swufe.edu.cn')->firstOrFail();
    }

    protected function orgUser(): User
    {
        return User::query()->where('email', 'org@swufe.edu.cn')->firstOrFail();
    }

    protected function collegeUser(): User
    {
        return User::query()->where('email', 'college@swufe.edu.cn')->firstOrFail();
    }

    protected function branchUser(): User
    {
        return User::query()->where('email', 'branch@swufe.edu.cn')->firstOrFail();
    }

    protected function memberUser(): User
    {
        return User::query()->where('email', 'member@swufe.edu.cn')->firstOrFail();
    }

    /**
     * 获取 Widget 的 getStats() 结果。
     *
     * @return array<int, Stat>
     */
    protected function invokeWidgetStats(object $widget): array
    {
        $method = new ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);

        return $method->invoke($widget);
    }

    public function test_sysadmin_panel_access_control(): void
    {
        $this->seedDb();

        $sysadminPanel = Filament::getPanel('sysadmin');

        // 超级管理员可进入 sysadmin 面板
        $super = $this->superUser();
        $this->assertTrue($super->canAccessPanel($sysadminPanel));

        // 停用管理员档案后失去权限
        Admin::query()->where('user_id', $super->id)->update(['status' => '停用']);
        $this->assertFalse($super->refresh()->canAccessPanel($sysadminPanel));

        // 重新启用恢复权限
        Admin::query()->where('user_id', $super->id)->update(['status' => '启用']);
        $this->assertTrue($super->refresh()->canAccessPanel($sysadminPanel));

        // 普通党员、支部书记、二级党委委员均不可进入 sysadmin 面板
        $this->assertFalse($this->memberUser()->canAccessPanel($sysadminPanel));
        $this->assertFalse($this->branchUser()->canAccessPanel($sysadminPanel));
        $this->assertFalse($this->collegeUser()->canAccessPanel($sysadminPanel));
    }

    public function test_sysadmin_panel_pages_render(): void
    {
        $this->seedDb();

        Filament::setCurrentPanel(Filament::getPanel('sysadmin'));
        $this->be($this->superUser());

        $this->get('/sysadmin')->assertSuccessful();
        $this->get('/sysadmin/audit-logs')->assertSuccessful();
        $this->get('/sysadmin/configs')->assertSuccessful();
        $this->get('/sysadmin/dicts')->assertSuccessful();
        $this->get('/sysadmin/users')->assertSuccessful();
    }

    public function test_branch_overview_widget_scoped_to_organization(): void
    {
        $this->seedDb();

        $branchUser = $this->branchUser();
        $this->be($branchUser);

        $widget = new BranchOverviewWidget;
        $stats = $this->invokeWidgetStats($widget);

        $this->assertCount(4, $stats);

        // 支部党员统计
        $memberStat = $stats[0];
        $this->assertSame('支部党员', $memberStat->getLabel());
        $this->assertStringContainsString('人', (string) $memberStat->getValue());

        // 组织生活统计
        $meetingStat = $stats[1];
        $this->assertSame('组织生活', $meetingStat->getLabel());

        // 发展党员统计
        $devStat = $stats[2];
        $this->assertSame('发展党员', $devStat->getLabel());

        // 待处置预警
        $alertStat = $stats[3];
        $this->assertSame('待处置预警', $alertStat->getLabel());
    }

    public function test_college_overview_widget_scoped_to_college(): void
    {
        $this->seedDb();

        $collegeUser = $this->collegeUser();
        $this->be($collegeUser);

        $widget = new CollegeOverviewWidget;
        $stats = $this->invokeWidgetStats($widget);

        $this->assertCount(4, $stats);
        $this->assertSame('辖属支部', $stats[0]->getLabel());
        $this->assertSame('所辖党员', $stats[1]->getLabel());
        $this->assertSame('待审事项', $stats[2]->getLabel());
        $this->assertSame('监督预警', $stats[3]->getLabel());

        // 验证只统计本学院分支（3 个支部）
        $this->assertSame('3 个', (string) $stats[0]->getValue());
    }

    public function test_org_overview_widget_campus_wide(): void
    {
        $this->seedDb();

        $orgUser = $this->orgUser();
        $this->be($orgUser);

        $widget = new OrgOverviewWidget;
        $stats = $this->invokeWidgetStats($widget);

        $this->assertCount(4, $stats);
        $this->assertSame('基层党组织', $stats[0]->getLabel());
        $this->assertSame('全校党员', $stats[1]->getLabel());
        $this->assertSame('组织部待审', $stats[2]->getLabel());
        $this->assertSame('督办与预警', $stats[3]->getLabel());

        // 3 个二级学院、7 个党支部（3+2+2）
        $this->assertSame('3 院 · 7 支', (string) $stats[0]->getValue());
    }

    public function test_member_overview_widget_personal_profile(): void
    {
        $this->seedDb();

        $memberUser = $this->memberUser();
        $this->be($memberUser);

        $widget = new MemberOverviewWidget;
        $stats = $this->invokeWidgetStats($widget);

        $this->assertCount(4, $stats);
        $this->assertSame('所在支部', $stats[0]->getLabel());
        $this->assertSame('政治生日', $stats[1]->getLabel());
        $this->assertSame('年度学时', $stats[2]->getLabel());
        $this->assertSame('当月党费', $stats[3]->getLabel());

        $this->assertNotEmpty($stats[0]->getValue());
        $this->assertNotEmpty($stats[1]->getValue());
    }

    public function test_audit_log_is_read_only_and_queryable(): void
    {
        $this->seedDb();

        $this->assertFalse(AuditLogResource::canCreate());

        $super = $this->superUser();

        // 写入一条审计记录
        AuditLog::query()->create([
            'user_id' => $super->id,
            'action' => 'update',
            'subject_type' => Config::class,
            'subject_id' => 1,
            'properties' => ['old' => '180', 'new' => '90'],
            'ip' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $super->id,
            'action' => 'update',
            'subject_type' => Config::class,
        ]);
    }

    public function test_dict_management_and_filtering(): void
    {
        $this->seedDb();

        // 字典项存在预设值
        $this->assertGreaterThan(0, Dict::query()->where('type', 'education')->count());

        // 新建自定义字典项
        $dict = Dict::query()->create([
            'type' => 'test_type',
            'key' => 'test_key',
            'label' => '测试标签',
            'sort_order' => 10,
            'enabled' => true,
        ]);

        $this->assertSame('测试标签', $dict->label);
        $this->assertTrue($dict->enabled);

        // 停用
        $dict->update(['enabled' => false]);
        $this->assertFalse($dict->refresh()->enabled);
    }

    public function test_config_management_and_runtime_effect(): void
    {
        $this->seedDb();

        // 默认预设配置
        $this->assertSame('180', Config::value('term_warning_days'));

        // 更新配置
        Config::query()->where('key', 'term_warning_days')->update(['value' => '90']);
        $this->assertSame('90', Config::value('term_warning_days'));

        // 兜底默认值
        $this->assertSame('default_val', Config::value('non_existent_key', 'default_val'));
    }

    public function test_committee_term_expiry_revokes_branch_access(): void
    {
        $this->seedDb();

        $branchPanel = Filament::getPanel('branch');
        $branchUser = $this->branchUser();

        // 初始在任：可进入 branch 面板
        $this->assertTrue($branchUser->canAccessPanel($branchPanel));

        // 任期届满已离任
        CommitteeMember::query()
            ->where('member_id', $branchUser->member_id)
            ->update([
                'status' => '离任',
                'term_end' => now()->subDay()->toDateString(),
            ]);

        // 动态失去 branch 面板进入权限
        $this->assertFalse($branchUser->refresh()->canAccessPanel($branchPanel));
    }
}
