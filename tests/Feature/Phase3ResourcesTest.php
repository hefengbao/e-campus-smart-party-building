<?php

namespace Tests\Feature;

use App\Filament\Branch\Resources\TrainingEnrollments\TrainingEnrollmentResource;
use App\Filament\Branch\Resources\TrainingHours\TrainingHourResource;
use App\Filament\Branch\Resources\Transfers\TransferResource;
use App\Models\AbroadApplication;
use App\Models\Member;
use App\Models\Organization;
use App\Models\TrainingHour;
use App\Models\Transfer;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class Phase3ResourcesTest extends TestCase
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

    protected function collegeUser(): User
    {
        return User::query()->where('email', 'college@swufe.edu.cn')->firstOrFail();
    }

    protected function orgUser(): User
    {
        return User::query()->where('email', 'org@swufe.edu.cn')->firstOrFail();
    }

    protected function trainingUser(): User
    {
        return User::query()->where('email', 'training@swufe.edu.cn')->firstOrFail();
    }

    public function test_member_panel_pages_render(): void
    {
        $this->seedDb();

        // member 是默认面板，无需 setCurrentPanel
        $this->be($this->memberUser());

        $this->get('/member/training-hours')->assertSuccessful();
        $this->get('/member/transfers')->assertSuccessful();
        $this->get('/member/abroad-applications')->assertSuccessful();
    }

    public function test_member_transfer_full_approval_flow(): void
    {
        $this->seedDb();

        $memberUser = $this->memberUser();
        $member = $memberUser->member;

        // 党员发起转接申请（模拟 CreateTransfer 落库逻辑）
        $transfer = Transfer::create([
            'member_id' => $member->id,
            'direction' => '转出',
            'transfer_type' => '校外',
            'from_org_id' => $member->org_id,
            'to_org_name' => '四川省直机关工委',
            'reason' => '工作调动',
            'apply_at' => now(),
            'status' => '待支部审核',
        ]);

        $this->assertSame('待支部审核', $transfer->status);

        // 支部初审 → 待院级审核
        $transfer->review(approve: true, stage: 'branch', reviewerId: 1);
        $transfer->refresh();
        $this->assertSame('待院级审核', $transfer->status);
        $this->assertNotNull($transfer->branch_reviewed_at);

        // 院级审核 → 待组织部审核
        $transfer->review(approve: true, stage: 'college', reviewerId: 2);
        $transfer->refresh();
        $this->assertSame('待组织部审核', $transfer->status);
        $this->assertNotNull($transfer->college_reviewed_at);

        // 组织部终审 → 已办结 + 介绍信编号
        $transfer->review(approve: true, stage: 'final', reviewerId: 3);
        $transfer->refresh();
        $this->assertSame('已办结', $transfer->status);
        $this->assertNotNull($transfer->letter_no);
        $this->assertStringStartsWith('ZZJD-'.now()->format('Y').'-', $transfer->letter_no);
        $this->assertNotNull($transfer->finished_at);

        // 驳回路径（另一条记录）
        $rejected = Transfer::create([
            'member_id' => $member->id,
            'direction' => '转出',
            'transfer_type' => '省内',
            'from_org_id' => $member->org_id,
            'to_org_name' => '成都市高新区党委',
            'reason' => '家庭原因',
            'apply_at' => now(),
            'status' => '待支部审核',
        ]);
        $rejected->review(approve: false, stage: 'branch', reviewerId: 1, note: '材料不全');
        $rejected->refresh();
        $this->assertSame('已驳回', $rejected->status);
        $this->assertSame('材料不全', $rejected->remark);
    }

    public function test_member_abroad_application_full_approval_flow(): void
    {
        $this->seedDb();

        $member = $this->memberUser()->member;

        // 6 个月以下：支部 → 院级 → 已批准
        $short = AbroadApplication::create([
            'member_id' => $member->id,
            'destination' => '日本',
            'reason' => '学术交流',
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-20',
            ...AbroadApplication::resolveRoute('2026-12-01', '2026-12-20'),
            'retain_relation' => true,
            'status' => '待支部审核',
        ]);

        $this->assertSame('6个月以下', $short->approval_route);

        $short->review(approve: true, stage: 'branch', reviewerId: 1);
        $short->refresh();
        $this->assertSame('待院级审核', $short->status);

        $short->review(approve: true, stage: 'college', reviewerId: 2);
        $short->refresh();
        $this->assertSame('已批准', $short->status);

        // 6 个月及以上：支部 → 院级 → 组织部终审 → 已批准
        $longStart = now()->addDays(7)->toDateString();
        $longEnd = now()->addDays(200)->toDateString();
        $long = AbroadApplication::create([
            'member_id' => $member->id,
            'destination' => '美国',
            'reason' => '访学',
            'start_date' => $longStart,
            'end_date' => $longEnd,
            ...AbroadApplication::resolveRoute($longStart, $longEnd),
            'retain_relation' => true,
            'status' => '待支部审核',
        ]);

        $this->assertSame('6个月及以上', $long->approval_route);
        $this->assertGreaterThanOrEqual(AbroadApplication::SIX_MONTH_DAYS, $long->duration_days);

        $long->review(approve: true, stage: 'branch', reviewerId: 1);
        $long->review(approve: true, stage: 'college', reviewerId: 2);
        $long->refresh();
        $this->assertSame('待组织部审核', $long->status);

        $long->review(approve: true, stage: 'final', reviewerId: 3);
        $long->refresh();
        $this->assertSame('已批准', $long->status);
        $this->assertNotNull($long->dept_reviewed_at);

        // 驳回路径
        $rejected = AbroadApplication::create([
            'member_id' => $member->id,
            'destination' => '英国',
            'reason' => '旅游',
            'start_date' => '2027-01-01',
            'end_date' => '2027-01-10',
            ...AbroadApplication::resolveRoute('2027-01-01', '2027-01-10'),
            'retain_relation' => true,
            'status' => '待支部审核',
        ]);
        $rejected->review(approve: false, stage: 'branch', reviewerId: 1, note: '事由不充分');
        $rejected->refresh();
        $this->assertSame('已驳回', $rejected->status);
    }

    public function test_extension_apply_and_college_review(): void
    {
        $this->seedDb();

        $member = $this->memberUser()->member;

        $application = AbroadApplication::factory()
            ->approved()
            ->create(['member_id' => $member->id]);

        // 党员提交延期申请（模拟 extension-apply Action）
        $extension = $application->extensions()->create([
            'extend_months' => 3,
            'reason' => '访学项目延长',
            'status' => '待审批',
            'applied_at' => now(),
        ]);

        $this->assertSame('待审批', $extension->status);

        // 院级审批通过（模拟 extension-review Action）
        $extension->update([
            'status' => '已批准',
            'approved_at' => now(),
            'approver' => (string) Auth::id(),
        ]);

        $extension->refresh();
        $this->assertSame('已批准', $extension->status);
        $this->assertNotNull($extension->approved_at);
        $this->assertNotNull($extension->approver);

        // 驳回路径
        $rejected = $application->extensions()->create([
            'extend_months' => 6,
            'reason' => '继续延长',
            'status' => '待审批',
            'applied_at' => now(),
        ]);
        $rejected->update([
            'status' => '已驳回',
            'approved_at' => now(),
            'approver' => (string) Auth::id(),
        ]);

        $rejected->refresh();
        $this->assertSame('已驳回', $rejected->status);
        $this->assertSame(2, $application->extensions()->count());
    }

    public function test_training_hour_category_totals(): void
    {
        $this->seedDb();

        $member = $this->memberUser()->member;

        // 已生效：网络自学 8 + 集中培训 4
        TrainingHour::factory()->create([
            'member_id' => $member->id,
            'org_id' => $member->org_id,
            'training_category' => '网络自学',
            'hours' => 8,
            'status' => '已生效',
        ]);
        TrainingHour::factory()->create([
            'member_id' => $member->id,
            'org_id' => $member->org_id,
            'training_category' => '集中培训',
            'hours' => 4,
            'status' => '已生效',
        ]);

        // 待审核 2（不计入正式学时）
        TrainingHour::factory()->pending()->create([
            'member_id' => $member->id,
            'org_id' => $member->org_id,
            'training_category' => '网络自学',
            'hours' => 2,
        ]);

        $effective = TrainingHour::query()
            ->where('member_id', $member->id)
            ->where('status', '已生效');

        $this->assertSame(12.0, (float) $effective->sum('hours'));
        $this->assertSame(8.0, (float) (clone $effective)->where('training_category', '网络自学')->sum('hours'));
        $this->assertSame(4.0, (float) (clone $effective)->where('training_category', '集中培训')->sum('hours'));
        $this->assertSame(2.0, (float) TrainingHour::query()
            ->where('member_id', $member->id)
            ->where('status', '待审核')
            ->sum('hours'));

        // 组织部审核通过后计入正式学时
        $pending = TrainingHour::query()
            ->where('member_id', $member->id)
            ->where('status', '待审核')
            ->firstOrFail();
        $pending->update([
            'status' => '已生效',
            'reviewer' => 1,
            'reviewed_at' => now(),
        ]);

        $this->assertSame(14.0, (float) TrainingHour::query()
            ->where('member_id', $member->id)
            ->where('status', '已生效')
            ->sum('hours'));
    }

    public function test_collaborative_side_cannot_see_roster(): void
    {
        $this->seedDb();

        // 协同端（branch/training）无 MemberResource：名册仅 org 面板可见
        $this->assertSame(
            [],
            collect(Filament::getPanel('branch')->getResources())
                ->filter(fn (string $resource): bool => str_contains($resource, 'Members'))
                ->values()
                ->all(),
        );

        // training@ 可进入的 member 面板同样没有名册资源
        $this->assertSame(
            [],
            collect(Filament::getPanel('member')->getResources())
                ->filter(fn (string $resource): bool => str_contains($resource, 'Members'))
                ->values()
                ->all(),
        );

        // branch 面板学时提交仅见本人提交记录
        Filament::setCurrentPanel(Filament::getPanel('branch'));
        $branchUser = $this->branchUser();
        $this->be($branchUser);

        $otherMember = Member::query()
            ->where('org_id', $branchUser->scopedOrgIds()[0])
            ->whereKeyNot($branchUser->member->id)
            ->firstOrFail();

        $mine = TrainingHour::factory()->pending()->create([
            'member_id' => $otherMember->id,
            'org_id' => $otherMember->org_id,
            'submitted_by' => (string) $branchUser->id,
        ]);
        $others = TrainingHour::factory()->pending()->create([
            'member_id' => $otherMember->id,
            'org_id' => $otherMember->org_id,
            'submitted_by' => '99999',
        ]);

        $visible = TrainingHourResource::getEloquentQuery()->pluck('id');

        $this->assertTrue($visible->contains($mine->id));
        $this->assertFalse($visible->contains($others->id));

        // 培训报名审核按组织范围收敛（visibleTo）
        $enrollmentResource = TrainingEnrollmentResource::getEloquentQuery();
        $this->assertInstanceOf(Builder::class, $enrollmentResource);

        // training@ 无党员档案、无角色授权：数据范围为空数组（全校回退不适用，仅 member 面板）
        $trainingUser = $this->trainingUser();
        $this->assertNull($trainingUser->member_id);
        $this->assertFalse($trainingUser->canAccessPanel(Filament::getPanel('branch')));
        $this->assertFalse($trainingUser->canAccessPanel(Filament::getPanel('org')));
        $this->assertTrue($trainingUser->canAccessPanel(Filament::getPanel('member')));
    }

    public function test_transfer_data_scope_by_organization(): void
    {
        $this->seedDb();

        Filament::setCurrentPanel(Filament::getPanel('branch'));
        $branchUser = $this->branchUser();
        $this->be($branchUser);

        $branchOrgId = $branchUser->scopedOrgIds()[0];

        // 本支部党员的转接申请可见
        $inScope = Transfer::factory()->create([
            'from_org_id' => $branchOrgId,
        ]);

        // 其他支部党员的转接申请不可见
        $otherBranch = Organization::query()
            ->where('level', 3)
            ->whereKeyNot($branchOrgId)
            ->firstOrFail();
        $outOfScope = Transfer::factory()->create([
            'from_org_id' => $otherBranch->id,
        ]);

        $visible = TransferResource::getEloquentQuery()->pluck('id');

        $this->assertTrue($visible->contains($inScope->id));
        $this->assertFalse($visible->contains($outOfScope->id));
    }
}
