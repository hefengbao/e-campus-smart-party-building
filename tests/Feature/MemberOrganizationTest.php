<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberOrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_branch_association_is_stored_in_pivot_with_one_primary_branch(): void
    {
        $branch = Organization::factory()->branch()->create();
        $member = Member::factory()->create(['org_id' => $branch->id]);

        $this->assertDatabaseHas('member_organization', [
            'member_id' => $member->id,
            'organization_id' => $branch->id,
            'is_primary' => true,
            'status' => '正常',
        ]);
        $this->assertSame($branch->id, $member->org_id);
        $this->assertSame($branch->id, $member->organization?->id);
        $this->assertTrue($branch->members()->whereKey($member->id)->exists());

        $otherBranch = Organization::factory()->branch()->create();
        $member->organizations()->attach($otherBranch->id, [
            'is_primary' => false,
            'status' => '正常',
        ]);

        $this->assertCount(2, $member->organizations()->get());
        $this->assertSame($branch->id, $member->fresh()->org_id);
        $this->assertTrue(Member::query()->visibleTo([$otherBranch->id])->whereKey($member->id)->exists());
    }

    public function test_changing_primary_branch_closes_old_link_and_promotes_new_link(): void
    {
        $branch = Organization::factory()->branch()->create();
        $newBranch = Organization::factory()->branch()->create();
        $member = Member::factory()->create(['org_id' => $branch->id]);

        $member->setPrimaryOrganization($newBranch);

        $this->assertDatabaseHas('member_organization', [
            'member_id' => $member->id,
            'organization_id' => $branch->id,
            'is_primary' => false,
            'status' => '已转出',
        ]);
        $this->assertDatabaseHas('member_organization', [
            'member_id' => $member->id,
            'organization_id' => $newBranch->id,
            'is_primary' => true,
            'status' => '正常',
        ]);
        $this->assertSame($newBranch->id, $member->fresh()->org_id);
    }
}
