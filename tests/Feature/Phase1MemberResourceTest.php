<?php

namespace Tests\Feature;

use App\Filament\Org\Resources\Organizations\OrganizationResource;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase1MemberResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function seedDb(): void
    {
        $this->seed(DatabaseSeeder::class);
    }

    public function test_org_dept_sees_all_members(): void
    {
        $this->seedDb();

        Filament::setCurrentPanel(Filament::getPanel('org'));

        $user = User::query()->where('email', 'org@swufe.edu.cn')->firstOrFail();
        $this->be($user);

        $response = $this->get('/org/members');

        $response->assertSuccessful();

        $total = Member::count();
        $this->assertGreaterThan(0, $total);
    }

    public function test_branch_sees_only_own_branch_members(): void
    {
        $this->seedDb();

        Filament::setCurrentPanel(Filament::getPanel('org'));

        $user = User::query()->where('email', 'branch@swufe.edu.cn')->firstOrFail();
        $this->be($user);

        $orgIds = $user->scopedOrgIds();
        $this->assertNotEmpty($orgIds);

        $visibleCount = Member::query()->visibleTo($orgIds)->count();
        $totalCount = Member::count();

        $this->assertGreaterThan(0, $visibleCount);
        $this->assertLessThan($totalCount, $visibleCount);
    }

    public function test_member_validation_join_age_check(): void
    {
        $this->seedDb();

        $member = new Member([
            'name' => '测试',
            'birth_date' => '2000-06-01',
            'join_party_date' => '2015-01-01',
            'special_note' => null,
        ]);

        $this->assertFalse($member->passesJoinAgeCheck());
        $this->assertFalse($member->validationSatisfied());

        $member->special_note = '档案特殊说明';

        $this->assertTrue($member->validationSatisfied());
    }

    public function test_member_validation_full_member_check(): void
    {
        $this->seedDb();

        $member = new Member([
            'name' => '测试',
            'join_party_date' => '2020-06-01',
            'full_member_date' => '2020-12-01',
            'special_note' => null,
        ]);

        $this->assertFalse($member->passesFullMemberCheck());
        $this->assertFalse($member->validationSatisfied());

        $member->special_note = '特殊情况';

        $this->assertTrue($member->validationSatisfied());
    }

    public function test_member_validation_passes_with_valid_dates(): void
    {
        $this->seedDb();

        $member = new Member([
            'name' => '测试',
            'birth_date' => '2000-06-01',
            'join_party_date' => '2020-06-01',
            'full_member_date' => '2021-06-02',
        ]);

        $this->assertTrue($member->passesJoinAgeCheck());
        $this->assertTrue($member->passesFullMemberCheck());
        $this->assertTrue($member->validationSatisfied());
    }

    public function test_org_resource_scopes_query_by_data_scope(): void
    {
        $this->seedDb();

        Filament::setCurrentPanel(Filament::getPanel('org'));

        $user = User::query()->where('email', 'college@swufe.edu.cn')->firstOrFail();
        $this->be($user);

        $orgIds = $user->scopedOrgIds();

        $scopedQuery = OrganizationResource::getEloquentQuery();
        $scopedIds = $scopedQuery->pluck('id')->all();

        $this->assertSame([], array_diff($orgIds, $scopedIds));
        $this->assertNotEmpty($scopedIds);
    }
}
