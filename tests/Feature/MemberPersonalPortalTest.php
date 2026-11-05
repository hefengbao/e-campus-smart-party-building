<?php

namespace Tests\Feature;

use App\Filament\Member\Resources\DevelopmentCases\DevelopmentCaseResource;
use App\Filament\Member\Resources\FeeRecords\FeeRecordResource;
use App\Filament\Member\Resources\TrainingHours\TrainingHourResource;
use App\Filament\Member\Widgets\MemberOverviewWidget;
use App\Filament\Member\Widgets\TrainingProfileWidget;
use App\Models\DevelopmentCase;
use App\Models\FeeRecord;
use App\Models\Member;
use App\Models\Organization;
use App\Models\PhoneChangeRequest;
use App\Models\Trainee;
use App\Models\TrainingHour;
use App\Models\User;
use App\Models\WorkshopActivity;
use Database\Seeders\DatabaseSeeder;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class MemberPersonalPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function seedDb(): void
    {
        $this->seed(DatabaseSeeder::class);
    }

    protected function memberUser(): User
    {
        return User::query()->where('email', 'member@swufe.edu.cn')->firstOrFail();
    }

    protected function branchUser(): User
    {
        return User::query()->where('email', 'branch@swufe.edu.cn')->firstOrFail();
    }

    protected function trainingUser(): User
    {
        return User::query()->where('email', 'training@swufe.edu.cn')->firstOrFail();
    }

    /**
     * @return array<int, Stat>
     */
    protected function invokeWidgetStats(object $widget): array
    {
        $method = new ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);

        return $method->invoke($widget);
    }

    public function test_member_panel_is_accessible_to_all_authenticated_users(): void
    {
        $this->seedDb();

        $memberPanel = Filament::getPanel('member');

        // 正式党员、支部书记、未关联党员档案的协同员均可访问默认工作台
        $this->assertTrue($this->memberUser()->canAccessPanel($memberPanel));
        $this->assertTrue($this->branchUser()->canAccessPanel($memberPanel));
        $this->assertTrue($this->trainingUser()->canAccessPanel($memberPanel));
    }

    public function test_member_panel_pages_render_successfully(): void
    {
        $this->seedDb();

        $this->be($this->memberUser());

        // 个人全业务入口访问畅通（member panel 作为根路径 /）
        $this->get('/')->assertSuccessful();
        $this->get('/fee-records')->assertSuccessful();
        $this->get('/training-hours')->assertSuccessful();
        $this->get('/phone-change-requests')->assertSuccessful();
        $this->get('/development-cases')->assertSuccessful();
        $this->get('/transfers')->assertSuccessful();
        $this->get('/abroad-applications')->assertSuccessful();
        $this->get('/workshop-activities')->assertSuccessful();
        $this->get('/workshop-posts')->assertSuccessful();
    }

    public function test_fee_record_is_scoped_to_current_member(): void
    {
        $this->seedDb();

        $member = $this->memberUser()->member;
        $otherMember = Member::query()->whereKeyNot($member->id)->firstOrFail();

        // 为本人创建党费记录
        $myFee = FeeRecord::query()->create([
            'member_id' => $member->id,
            'period' => '2026-11',
            'amount' => 15.00,
            'pay_status' => '已交纳',
            'pay_date' => '2026-11-01',
            'source' => '党费系统同步',
        ]);

        // 为他人创建党费记录
        $otherFee = FeeRecord::query()->create([
            'member_id' => $otherMember->id,
            'period' => '2026-11',
            'amount' => 20.00,
            'pay_status' => '已交纳',
            'pay_date' => '2026-11-01',
            'source' => '党费系统同步',
        ]);

        $this->be($this->memberUser());

        $visibleIds = FeeRecordResource::getEloquentQuery()->pluck('id');

        $this->assertTrue($visibleIds->contains($myFee->id));
        $this->assertFalse($visibleIds->contains($otherFee->id));
    }

    public function test_phone_change_request_flow(): void
    {
        $this->seedDb();

        $user = $this->memberUser();
        $member = $user->member;
        $this->be($user);

        // 个人发起手机号变更申请
        $request = PhoneChangeRequest::query()->create([
            'member_id' => $member->id,
            'old_phone' => $member->phone,
            'new_phone' => '13988887777',
            'reason' => '换了新号码',
            'status' => '待审核',
        ]);

        $this->assertSame('待审核', $request->status);

        // 模拟支部书记审核通过
        $request->member->update(['phone' => $request->new_phone]);
        $request->update([
            'status' => '已通过',
            'reviewed_by' => $this->branchUser()->id,
            'reviewed_at' => now(),
        ]);

        $member->refresh();
        $this->assertSame('13988887777', $member->phone);
        $this->assertSame('已通过', $request->refresh()->status);
    }

    public function test_development_case_is_scoped_to_current_member(): void
    {
        $this->seedDb();

        $member = $this->memberUser()->member;
        $branch = Organization::query()->where('level', 3)->firstOrFail();

        // 模拟一人一案发展纪实
        $myCase = DevelopmentCase::factory()->create([
            'member_id' => $member->id,
            'org_id' => $branch->id,
            'stage_no' => 2,
            'step_no' => 6,
            'status' => '进行中',
        ]);

        $otherCase = DevelopmentCase::factory()->create([
            'org_id' => $branch->id,
            'stage_no' => 1,
            'step_no' => 1,
            'status' => '进行中',
        ]);

        $this->be($this->memberUser());

        $visibleIds = DevelopmentCaseResource::getEloquentQuery()->pluck('id');

        $this->assertTrue($visibleIds->contains($myCase->id));
        $this->assertFalse($visibleIds->contains($otherCase->id));
    }

    public function test_non_party_cadre_can_view_training_hours_and_overview(): void
    {
        $this->seedDb();

        $trainingUser = $this->trainingUser();
        $this->be($trainingUser);

        // 为该用户创建党外干部学员档案（工号与 User.id 一致）
        $trainee = Trainee::query()->create([
            'staff_no' => $trainingUser->id,
            'name' => $trainingUser->name,
            'department' => '教务处',
            'position' => '副处长',
            'cadre_level' => '副处级',
            'cadre_type' => '党外干部',
            'political_status' => '群众',
            'is_party_member' => false,
            'member_id' => null,
            'status' => '在训',
            'enabled' => true,
        ]);

        // 录入已生效学时
        $hour = TrainingHour::query()->create([
            'trainee_id' => $trainee->id,
            'title' => '高校党外代表人士培训班',
            'training_category' => '集中培训',
            'training_form' => '专题研讨',
            'hours' => 16.0,
            'occurred_on' => now()->toDateString(),
            'year' => (int) now()->format('Y'),
            'status' => '已生效',
        ]);

        // 验证个人学时流水能查到
        $visibleHourIds = TrainingHourResource::getEloquentQuery()->pluck('id');
        $this->assertTrue($visibleHourIds->contains($hour->id));

        // 验证个人 Overview Widget 适配党外干部展示
        $overviewWidget = new MemberOverviewWidget;
        $stats = $this->invokeWidgetStats($overviewWidget);
        $this->assertCount(4, $stats);
        $this->assertSame('干部培训档案', $stats[0]->getLabel());
        $this->assertSame('政治面貌', $stats[1]->getLabel());

        // 验证 TrainingProfileWidget 正确统计已生效学时
        $profileWidget = new TrainingProfileWidget;
        $profileStats = $this->invokeWidgetStats($profileWidget);
        $this->assertSame('总学时', $profileStats[0]->getLabel());
        $this->assertSame(16.0, $profileStats[0]->getValue());
    }

    public function test_workshop_activities_accessible_to_all_members(): void
    {
        $this->seedDb();

        $college = Organization::query()->where('level', 2)->firstOrFail();

        // 公开已发布活动
        $publicActivity = WorkshopActivity::factory()->published()->create([
            'org_id' => $college->id,
            'is_public' => true,
            'title' => '全校党务骨干经验分享会',
        ]);

        // 不公开活动
        $privateActivity = WorkshopActivity::factory()->create([
            'org_id' => $college->id,
            'is_public' => false,
            'status' => '草稿',
            'title' => '内部交流草稿',
        ]);

        $this->be($this->memberUser());

        $this->get('/workshop-activities')->assertSuccessful();
        $this->get("/workshop-activities/{$publicActivity->id}")->assertSuccessful();
    }

    public function test_topbar_panel_switcher_renders_and_filters_by_user_permissions(): void
    {
        $this->seedDb();

        // 1. 支部书记拥有 member 和 branch 权限
        $branchUser = $this->branchUser();
        Filament::setCurrentPanel(Filament::getPanel('branch'));
        $this->be($branchUser);

        $response = $this->get('/branch');
        $response->assertSuccessful();
        $response->assertSee('fi-panel-switcher');
        $response->assertSee('@media (max-width: 639px)', false);
        $response->assertSee('.fi-panel-switcher-label', false);
        $response->assertSee('.fi-panel-switcher-chevron', false);
        $response->assertSee('党支部管理');
        $response->assertSee('个人工作台');
        // 支部书记无 org/college 权限，不能看到它们
        $response->assertDontSee('党委组织部');
        $response->assertDontSee('二级党委管理');

        // 2. 普通党员只拥有 member 权限
        Filament::setCurrentPanel(Filament::getPanel('member'));
        $this->be($this->memberUser());

        $memberResponse = $this->get('/');
        $memberResponse->assertSuccessful();
        $memberResponse->assertSee('fi-panel-switcher');
        $memberResponse->assertSee('个人工作台');
        $memberResponse->assertDontSee('党支部管理');
        $memberResponse->assertDontSee('党委组织部');
    }

    public function test_avatar_menu_shows_personal_info_in_every_panel(): void
    {
        $this->seedDb();

        $panelUsers = [
            ['member', 'member@swufe.edu.cn', '/'],
            ['branch', 'branch@swufe.edu.cn', '/branch'],
            ['college', 'college@swufe.edu.cn', '/college'],
            ['org', 'org@swufe.edu.cn', '/org'],
            ['sysadmin', 'super@swufe.edu.cn', '/sysadmin'],
        ];

        foreach ($panelUsers as [$panelId, $email, $url]) {
            $user = User::query()->where('email', $email)->firstOrFail();
            Filament::setCurrentPanel(Filament::getPanel($panelId));
            $this->be($user);

            $response = $this->get($url);

            $response->assertSuccessful();
            $response->assertSee('fi-user-personal-info');
            $response->assertSee($user->name);
            $response->assertSee($user->email);

            $phone = $user->phone ?: $user->member?->phone;

            if (filled($phone)) {
                $response->assertSee($phone);
            }
        }
    }
}
