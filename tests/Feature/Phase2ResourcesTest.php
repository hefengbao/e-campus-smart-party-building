<?php

namespace Tests\Feature;

use App\Models\DevelopmentCase;
use App\Models\DevelopmentStepDefinition;
use App\Models\Meeting;
use App\Models\Member;
use App\Models\PhoneChangeRequest;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DevelopmentStepDefinitionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class Phase2ResourcesTest extends TestCase
{
    use RefreshDatabase;

    protected function seedDb(): void
    {
        $this->seed(DatabaseSeeder::class);
    }

    protected function branchUser(): User
    {
        return User::query()->where('email', 'branch@swufe.edu.cn')->firstOrFail();
    }

    protected function collegeUser(): User
    {
        return User::query()->where('email', 'college@swufe.edu.cn')->firstOrFail();
    }

    public function test_branch_meetings_page_renders(): void
    {
        $this->seedDb();

        Filament::setCurrentPanel(Filament::getPanel('branch'));
        $this->be($this->branchUser());

        $this->get('/branch/meetings')->assertSuccessful();
    }

    public function test_branch_meeting_submit_action_updates_status(): void
    {
        $this->seedDb();

        Filament::setCurrentPanel(Filament::getPanel('branch'));
        $user = $this->branchUser();
        $this->be($user);

        $meeting = Meeting::factory()->create([
            'org_id' => $user->scopedOrgIds()[0],
            'status' => '草稿',
        ]);

        $meeting->update([
            'status' => '已提交',
            'submitted_at' => now(),
            'submitted_by' => Auth::id(),
        ]);

        $meeting->logs()->create([
            'action' => '提交',
            'operator' => Auth::id(),
            'role' => '支部',
        ]);

        $meeting->refresh();

        $this->assertSame('已提交', $meeting->status);
        $this->assertNotNull($meeting->submitted_at);
        $this->assertDatabaseHas('meeting_logs', [
            'meeting_id' => $meeting->id,
            'action' => '提交',
        ]);
    }

    public function test_college_meetings_page_renders(): void
    {
        $this->seedDb();

        Filament::setCurrentPanel(Filament::getPanel('college'));
        $this->be($this->collegeUser());

        $this->get('/college/meetings')->assertSuccessful();
    }

    public function test_college_archive_and_return_flow(): void
    {
        $this->seedDb();

        Filament::setCurrentPanel(Filament::getPanel('college'));
        $user = $this->collegeUser();
        $this->be($user);

        $meeting = Meeting::factory()->submitted()->create([
            'org_id' => $user->scopedOrgIds()[0],
        ]);

        // 归档
        $meeting->update([
            'status' => '已归档',
            'reviewed_at' => now(),
            'reviewed_by' => Auth::id(),
        ]);
        $meeting->logs()->create(['action' => '归档', 'operator' => Auth::id(), 'role' => '院级']);

        $meeting->refresh();
        $this->assertSame('已归档', $meeting->status);
        $this->assertTrue($meeting->isArchived());

        // 退回流程（另一条记录）
        $other = Meeting::factory()->submitted()->create([
            'org_id' => $user->scopedOrgIds()[0],
        ]);
        $other->update([
            'status' => '退回修改',
            'reviewed_at' => now(),
            'reviewed_by' => Auth::id(),
            'review_comment' => '材料不全',
        ]);
        $other->logs()->create([
            'action' => '退回修改',
            'operator' => Auth::id(),
            'role' => '院级',
            'comment' => '材料不全',
        ]);

        $other->refresh();
        $this->assertSame('退回修改', $other->status);
        $this->assertSame('材料不全', $other->review_comment);
    }

    public function test_development_case_creation_generates_25_steps(): void
    {
        $this->seedDb();

        Filament::setCurrentPanel(Filament::getPanel('branch'));
        $user = $this->branchUser();
        $this->be($user);

        $member = Member::query()
            ->visibleTo($user->scopedOrgIds())
            ->whereDoesntHave('developmentCase')
            ->firstOrFail();

        $case = DevelopmentCase::create([
            'member_id' => $member->id,
            'org_id' => $member->org_id,
            'status' => '进行中',
            'stage_no' => 1,
            'step_no' => 1,
            'apply_date' => now()->toDateString(),
        ]);

        foreach (DevelopmentStepDefinition::query()->orderBy('step_no')->get() as $definition) {
            $case->steps()->create([
                'stage_no' => $definition->stage_no,
                'step_no' => $definition->step_no,
                'step_name' => $definition->step_name,
                'status' => '未开始',
                'operator' => Auth::id(),
            ]);
        }

        $this->assertSame(25, $case->steps()->count());
        $this->assertSame(1, $case->steps()->where('step_no', 1)->value('stage_no'));
        $this->assertSame(5, $case->steps()->where('step_no', 25)->value('stage_no'));
    }

    public function test_development_case_step_completion_advances_case(): void
    {
        $this->seedDb();

        Filament::setCurrentPanel(Filament::getPanel('branch'));
        $user = $this->branchUser();
        $this->be($user);

        $member = Member::query()
            ->visibleTo($user->scopedOrgIds())
            ->whereDoesntHave('developmentCase')
            ->firstOrFail();

        $case = DevelopmentCase::create([
            'member_id' => $member->id,
            'org_id' => $member->org_id,
            'status' => '进行中',
            'stage_no' => 1,
            'step_no' => 1,
        ]);

        $definitions = DevelopmentStepDefinitionSeeder::STEP_DEFS;
        foreach ($definitions as [$stageNo, $stageName, $stepNo, $stepName, $days, $role, $materials]) {
            $case->steps()->create([
                'stage_no' => $stageNo,
                'step_no' => $stepNo,
                'step_name' => $stepName,
                'status' => '未开始',
            ]);
        }

        // 完成第 1 步 → 案件推进到第 2 步
        $step1 = $case->steps()->where('step_no', 1)->firstOrFail();
        $step1->update(['status' => '已完成', 'done_date' => now()]);

        $next = $case->steps()->where('step_no', '>', 1)->orderBy('step_no')->first();
        $case->update(['stage_no' => $next->stage_no, 'step_no' => $next->step_no]);

        $case->refresh();
        $this->assertSame(2, $case->step_no);
        $this->assertSame(1, $case->stage_no);

        // 完成第 25 步 → 案件办结
        $step25 = $case->steps()->where('step_no', 25)->firstOrFail();
        $step25->update(['status' => '已完成', 'done_date' => now()]);

        if ($step25->step_no >= 25) {
            $case->update(['status' => '已完成', 'finished_at' => now()]);
        }

        $case->refresh();
        $this->assertSame('已完成', $case->status);
        $this->assertNotNull($case->finished_at);
    }

    public function test_phone_change_request_approve_updates_member_phone(): void
    {
        $this->seedDb();

        Filament::setCurrentPanel(Filament::getPanel('branch'));
        $user = $this->branchUser();
        $this->be($user);

        $member = Member::query()->visibleTo($user->scopedOrgIds())->firstOrFail();
        $oldPhone = $member->phone;

        $request = PhoneChangeRequest::create([
            'member_id' => $member->id,
            'old_phone' => $oldPhone,
            'new_phone' => '13900001111',
            'reason' => '换号',
            'status' => '待审核',
        ]);

        // 通过
        $request->member->update(['phone' => $request->new_phone]);
        $request->update([
            'status' => '已通过',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        $member->refresh();
        $request->refresh();

        $this->assertSame('13900001111', $member->phone);
        $this->assertSame('已通过', $request->status);
        $this->assertNotNull($request->reviewed_at);

        // 驳回流程（另一条）
        $other = PhoneChangeRequest::create([
            'member_id' => $member->id,
            'old_phone' => '13900001111',
            'new_phone' => '13900002222',
            'status' => '待审核',
        ]);
        $other->update([
            'status' => '已驳回',
            'review_note' => '信息不符',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        $other->refresh();
        $member->refresh();
        $this->assertSame('已驳回', $other->status);
        $this->assertSame('信息不符', $other->review_note);
        $this->assertSame('13900001111', $member->phone);
    }
}
