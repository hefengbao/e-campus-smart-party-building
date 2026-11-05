<?php

namespace Tests\Feature;

use App\Filament\Branch\Resources\Alerts\AlertResource;
use App\Filament\Branch\Resources\BirthdayPushes\BirthdayPushResource;
use App\Jobs\PushBirthdayJob;
use App\Jobs\ScanAlertsJob;
use App\Models\Alert;
use App\Models\BirthdayPush;
use App\Models\Config;
use App\Models\FeeRecord;
use App\Models\Member;
use App\Models\Organization;
use App\Models\SupervisionTask;
use App\Models\User;
use App\Models\WorkshopActivity;
use App\Notifications\PoliticalBirthday;
use App\Support\SensitiveWords;
use Database\Seeders\DatabaseSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase4ResourcesTest extends TestCase
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

    protected function orgUser(): User
    {
        return User::query()->where('email', 'org@swufe.edu.cn')->firstOrFail();
    }

    public function test_workshop_public_activity_requires_review_before_publish(): void
    {
        $this->seedDb();

        $orgUser = $this->orgUser();
        $college = Organization::query()->where('level', 2)->firstOrFail();

        // 公开活动：草稿 → 提交 → 待审核（未发布）
        $activity = WorkshopActivity::factory()
            ->for($college, 'organization')
            ->create([
                'is_public' => true,
                'status' => '草稿',
                'created_by' => $orgUser->id,
            ]);

        $this->assertTrue($activity->isSubmittable());

        $activity->forceFill(['status' => '待审核'])->save();
        $activity->refresh();

        $this->assertSame('待审核', $activity->status);
        $this->assertNull($activity->published_at);
        $this->assertSame('待审核', $activity->effectiveStatus());

        // 组织部审核通过 → 已发布 + 留痕
        $activity->forceFill([
            'status' => '已发布',
            'reviewed_by' => (string) $orgUser->id,
            'reviewed_at' => now(),
            'published_at' => now(),
        ])->save();
        $activity->refresh();

        $this->assertSame('已发布', $activity->status);
        $this->assertNotNull($activity->published_at);
        $this->assertNotNull($activity->reviewed_at);
        $this->assertSame('报名中', $activity->effectiveStatus());

        // 已发布不可再提交
        $this->assertFalse($activity->isSubmittable());
    }

    public function test_workshop_reject_then_resubmit_flow(): void
    {
        $this->seedDb();

        $orgUser = $this->orgUser();
        $college = Organization::query()->where('level', 2)->firstOrFail();

        $activity = WorkshopActivity::factory()
            ->for($college, 'organization')
            ->create([
                'is_public' => true,
                'status' => '待审核',
                'created_by' => $orgUser->id,
            ]);

        // 驳回：必填原因
        $activity->forceFill([
            'status' => '已驳回',
            'reject_reason' => '活动说明不完整',
            'reviewed_by' => (string) $orgUser->id,
            'reviewed_at' => now(),
        ])->save();
        $activity->refresh();

        $this->assertSame('已驳回', $activity->status);
        $this->assertSame('活动说明不完整', $activity->reject_reason);
        $this->assertSame('已驳回', $activity->effectiveStatus());

        // 已驳回可重新提交
        $this->assertTrue($activity->isSubmittable());

        $activity->forceFill(['status' => '待审核'])->save();
        $this->assertSame('待审核', $activity->refresh()->status);
    }

    public function test_workshop_private_activity_publishes_directly(): void
    {
        $this->seedDb();

        $college = Organization::query()->where('level', 2)->firstOrFail();

        // 不公开活动：保存即发布，无需审核
        $activity = WorkshopActivity::factory()
            ->for($college, 'organization')
            ->create([
                'is_public' => false,
                'status' => '草稿',
            ]);

        $activity->forceFill([
            'status' => '已发布',
            'published_at' => now(),
        ])->save();
        $activity->refresh();

        $this->assertSame('已发布', $activity->status);
        $this->assertNull($activity->reviewed_at);
        $this->assertSame('报名中', $activity->effectiveStatus());
    }

    public function test_workshop_archive_window(): void
    {
        $this->seedDb();

        // 活动结束 3 天：窗口内可归档
        $inWindow = WorkshopActivity::factory()->ended()->create([
            'end_at' => now()->subDays(3),
        ]);
        $this->assertTrue($inWindow->isWithinArchiveWindow());

        $inWindow->forceFill([
            'summary' => '活动圆满结束，共 40 人参加。',
            'archived_at' => now(),
        ])->save();
        $inWindow->refresh();

        $this->assertNotNull($inWindow->archived_at);
        $this->assertNotNull($inWindow->summary);

        // 活动结束 10 天：窗口外不可归档
        $outOfWindow = WorkshopActivity::factory()->ended()->create([
            'end_at' => now()->subDays(10),
        ]);
        $this->assertFalse($outOfWindow->isWithinArchiveWindow());
    }

    public function test_sensitive_words_filter(): void
    {
        $this->seedDb();

        // 默认词库命中
        $this->assertSame('赌博', SensitiveWords::hit('这是赌博网站'));
        $this->assertSame('涉密', SensitiveWords::hit('涉及涉密信息'));
        $this->assertNull(SensitiveWords::hit('今天天气不错，适合开展党建活动。'));

        // 自定义词库生效（覆盖默认词库）
        Config::query()->updateOrCreate(
            ['key' => 'workshop_sensitive_words'],
            ['value' => '敏感词甲，敏感词乙', 'name' => '工作坊敏感词'],
        );

        $this->assertSame('敏感词甲', SensitiveWords::hit('包含敏感词甲的内容'));
        $this->assertNull(SensitiveWords::hit('这是赌博网站'));
    }

    public function test_scan_alerts_generates_dedups_and_respects_handled(): void
    {
        $this->seedDb();

        $branch = Organization::query()->where('level', 3)->firstOrFail();

        // 造数据：督办任务 3 天后到期 → 党建任务督办预警
        $task = SupervisionTask::factory()
            ->for($branch, 'organization')
            ->create(['due_date' => now()->addDays(3)->format('Y-m-d')]);

        // 造数据：预备党员 11 个月前入党 → 预备期到期预警（30 天内）
        $probationary = Member::factory()
            ->probationary()
            ->create([
                'org_id' => $branch->id,
                'join_party_date' => now()->subMonths(11)->format('Y-m-d'),
            ]);

        (new ScanAlertsJob)->handle();

        // 督办预警生成
        $taskAlert = Alert::query()
            ->where('rule', '党建任务督办')
            ->where('biz_type', 'supervision_task')
            ->where('biz_id', $task->id)
            ->firstOrFail();

        $this->assertSame('未处置', $taskAlert->status);
        $this->assertSame('提示', $taskAlert->level);
        $this->assertSame($branch->id, $taskAlert->org_id);

        // 预备期预警生成
        $probationAlert = Alert::query()
            ->where('rule', '预备期到期')
            ->where('member_id', $probationary->id)
            ->firstOrFail();

        $this->assertSame('提示', $probationAlert->level);
        $this->assertSame('member', $probationAlert->biz_type);
        $this->assertNull($probationAlert->biz_id);

        // 幂等：重复扫描不新增，仅刷新 last_scanned_at
        $countBefore = Alert::query()->count();
        $scannedAt = $taskAlert->last_scanned_at;

        $this->travel(1)->minutes();
        (new ScanAlertsJob)->handle();

        $this->assertSame($countBefore, Alert::query()->count());

        $taskAlert->refresh();
        $this->assertTrue($taskAlert->last_scanned_at->gt($scannedAt));

        // 已处置不复活
        $taskAlert->handle('已处置', 1, '已线下督办');
        (new ScanAlertsJob)->handle();

        $taskAlert->refresh();
        $this->assertSame('已处置', $taskAlert->status);
        $this->assertSame(1, Alert::query()
            ->where('dedup_key', $taskAlert->dedup_key)
            ->count());
    }

    public function test_alert_data_scope_and_branch_resource(): void
    {
        $this->seedDb();

        Filament::setCurrentPanel(Filament::getPanel('branch'));
        $branchUser = $this->branchUser();
        $this->be($branchUser);

        $branchOrgId = $branchUser->scopedOrgIds()[0];

        $inScope = Alert::factory()->create(['org_id' => $branchOrgId]);
        $otherBranch = Organization::query()
            ->where('level', 3)
            ->whereKeyNot($branchOrgId)
            ->firstOrFail();
        $outOfScope = Alert::factory()->create(['org_id' => $otherBranch->id]);

        $visible = AlertResource::getEloquentQuery()->pluck('id');

        $this->assertTrue($visible->contains($inScope->id));
        $this->assertFalse($visible->contains($outOfScope->id));

        // 处置留痕
        $inScope->handle('已处置', (int) $branchUser->id, '已核实处理');
        $inScope->refresh();

        $this->assertSame('已处置', $inScope->status);
        $this->assertNotNull($inScope->handled_at);
        $this->assertSame('已核实处理', $inScope->handle_note);

        // 忽略后不可再处置（幂等）
        $outOfScope->handle('已忽略', (int) $branchUser->id);
        $outOfScope->handle('已处置', (int) $branchUser->id);
        $outOfScope->refresh();

        $this->assertSame('已忽略', $outOfScope->status);

        // Branch 面板页面可访问
        $this->get('/branch/alerts')->assertSuccessful();
    }

    public function test_birthday_push_job_creates_ledger_and_notifies(): void
    {
        $this->seedDb();

        $branch = Organization::query()->where('level', 3)->firstOrFail();

        // 今天是入党周年（5 年前今天入党），关联账号
        $memberUser = $this->memberUser();
        $member = $memberUser->member;
        $member->forceFill([
            'join_party_date' => now()->subYears(5)->format('Y-m-d'),
        ])->save();

        // 8 天后周年：窗口外不生成
        $farMember = Member::factory()
            ->create([
                'org_id' => $branch->id,
                'join_party_date' => now()->subYears(3)->addDays(8)->format('Y-m-d'),
            ]);

        (new PushBirthdayJob)->handle();

        // 台账：站内 + 门户两条
        $pushes = BirthdayPush::query()
            ->where('member_id', $member->id)
            ->get();

        $this->assertSame(2, $pushes->count());
        $this->assertSame(5, $pushes->first()->years);
        $this->assertSame(
            now()->format('Y-m-d'),
            $pushes->first()->birthday_date->format('Y-m-d'),
        );

        // 当天到期：门户直接标记已推送
        $portal = $pushes->firstWhere('channel', '门户');
        $this->assertNotNull($portal);
        $this->assertSame('已推送', $portal->status);
        $this->assertNotNull($portal->pushed_at);

        // 站内台账待推送（由站内通知承载）
        $inApp = $pushes->firstWhere('channel', '站内');
        $this->assertSame('待推送', $inApp->status);

        // 站内通知已发送
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $memberUser->id,
            'type' => PoliticalBirthday::class,
        ]);

        // 窗口外不生成
        $this->assertSame(0, BirthdayPush::query()->where('member_id', $farMember->id)->count());

        // 幂等：重复执行不新增
        (new PushBirthdayJob)->handle();
        $this->assertSame(2, BirthdayPush::query()->where('member_id', $member->id)->count());
    }

    public function test_branch_birthday_push_resource_and_scope(): void
    {
        $this->seedDb();

        Filament::setCurrentPanel(Filament::getPanel('branch'));
        $branchUser = $this->branchUser();
        $this->be($branchUser);

        $branchOrgId = $branchUser->scopedOrgIds()[0];

        $inScopeMember = Member::factory()->create(['org_id' => $branchOrgId]);
        $otherBranch = Organization::query()
            ->where('level', 3)
            ->whereKeyNot($branchOrgId)
            ->firstOrFail();
        $outOfScopeMember = Member::factory()->create(['org_id' => $otherBranch->id]);

        $inScope = BirthdayPush::factory()->create(['member_id' => $inScopeMember->id]);
        BirthdayPush::factory()->create(['member_id' => $outOfScopeMember->id]);

        $visible = BirthdayPushResource::getEloquentQuery()->pluck('id');

        $this->assertTrue($visible->contains($inScope->id));
        $this->assertSame(1, $visible->count());

        // Branch 面板页面可访问
        $this->get('/branch/birthday-pushes')->assertSuccessful();
    }

    public function test_fee_sync_requires_token(): void
    {
        $this->seedDb();

        // 无令牌 → 401
        $this->post('/fee/sync', ['records' => []])
            ->assertStatus(401);

        // 错误令牌 → 401
        Config::query()->create([
            'key' => 'fee_sync_token',
            'value' => Hash::make('correct-token'),
            'name' => '党费同步令牌',
        ]);

        $this->post('/fee/sync', ['records' => []], ['X-Fee-Token' => 'wrong-token'])
            ->assertStatus(401);
    }

    public function test_fee_sync_creates_updates_and_reports_errors(): void
    {
        $this->seedDb();

        Config::query()->create([
            'key' => 'fee_sync_token',
            'value' => Hash::make('correct-token'),
            'name' => '党费同步令牌',
        ]);

        $member = $this->memberUser()->member;
        $otherMember = Member::query()->whereKeyNot($member->id)->firstOrFail();

        $payload = [
            'records' => [
                // staff_no 精确匹配 → 新建
                [
                    'staff_no' => $member->staff_no,
                    'period' => '2026-10',
                    'amount' => 12.5,
                    'pay_status' => '已交纳',
                    'pay_date' => '2026-10-15',
                ],
                // id_card 匹配（sha256 摘要）→ 新建
                [
                    'id_card' => $otherMember->id_card,
                    'period' => '2026-10',
                    'amount' => 8,
                ],
                // 未匹配 → failed
                [
                    'staff_no' => 'NO-SUCH-STAFF',
                    'period' => '2026-10',
                    'amount' => 1,
                ],
                // period 格式非法 → 校验失败整批 422
            ],
        ];

        // 先去掉非法记录验证正常流
        unset($payload['records'][3]);

        $response = $this->post('/fee/sync', $payload, ['X-Fee-Token' => 'correct-token']);

        $response->assertOk()->assertJson([
            'created' => 2,
            'updated' => 0,
            'failed' => 1,
        ]);

        $errors = $response->json('errors');
        $this->assertCount(1, $errors);
        $this->assertSame(2, $errors[0]['index']);

        // 落库校验
        $first = FeeRecord::query()
            ->where('member_id', $member->id)
            ->where('period', '2026-10')
            ->firstOrFail();

        $this->assertSame('12.50', (string) $first->amount);
        $this->assertSame('已交纳', $first->pay_status);
        $this->assertSame('党费系统同步', $first->source);

        $second = FeeRecord::query()
            ->where('member_id', $otherMember->id)
            ->where('period', '2026-10')
            ->firstOrFail();

        $this->assertSame('8.00', (string) $second->amount);
        $this->assertSame('已交纳', $second->pay_status);

        // 同 (member_id, period) 再推 → 更新不新增
        $response = $this->post('/fee/sync', [
            'records' => [
                [
                    'staff_no' => $member->staff_no,
                    'period' => '2026-10',
                    'amount' => 15,
                    'pay_status' => '未交纳',
                ],
            ],
        ], ['X-Fee-Token' => 'correct-token']);

        $response->assertOk()->assertJson([
            'created' => 0,
            'updated' => 1,
            'failed' => 0,
        ]);

        $this->assertSame(1, FeeRecord::query()
            ->where('member_id', $member->id)
            ->where('period', '2026-10')
            ->count());

        $first->refresh();
        $this->assertSame('15.00', (string) $first->amount);
        $this->assertSame('未交纳', $first->pay_status);
        $this->assertNull($first->pay_date);
    }

    public function test_fee_sync_validates_period_format(): void
    {
        $this->seedDb();

        Config::query()->create([
            'key' => 'fee_sync_token',
            'value' => Hash::make('correct-token'),
            'name' => '党费同步令牌',
        ]);

        // period 非 YYYY-MM → 422
        $this->postJson('/fee/sync', [
            'records' => [
                ['staff_no' => 'X', 'period' => '202610', 'amount' => 1],
            ],
        ], ['X-Fee-Token' => 'correct-token'])
            ->assertStatus(422);
    }
}
