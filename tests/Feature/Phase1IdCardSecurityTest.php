<?php

namespace Tests\Feature;

use App\Models\Member;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class Phase1IdCardSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function seedDb(): void
    {
        $this->seed(DatabaseSeeder::class);
    }

    public function test_id_card_is_encrypted_at_rest(): void
    {
        $this->seedDb();
        $member = Member::query()->firstOrFail();

        $raw = $member->getRawOriginal('id_card');

        $this->assertNotSame($member->id_card, $raw);
        $this->assertSame($member->id_card, Crypt::decryptString($raw));
    }

    public function test_id_card_masked_display_keeps_first_and_last_four(): void
    {
        $this->seedDb();

        $member = Member::factory()->create([
            'id_card' => '510101199001011234',
        ]);

        $masked = $member->masked_id_card;

        $this->assertSame('5101***********1234', $masked);
        $this->assertStringStartsWith('5101', $masked);
        $this->assertStringEndsWith('1234', $masked);
        $this->assertStringNotContainsString('19900101', $masked);
    }

    public function test_masked_id_card_returns_dash_when_empty(): void
    {
        $this->seedDb();

        $member = new Member(['id_card' => null]);

        $this->assertSame('—', $member->masked_id_card);
    }

    public function test_id_card_unique_across_members(): void
    {
        $this->seedDb();

        $first = Member::query()->firstOrFail();

        // 加密列无法依赖数据库唯一约束，唯一性由应用层校验保证
        $this->assertTrue(Member::idCardExists($first->id_card));
        $this->assertFalse(Member::idCardExists('510101199001019999'));
        $this->assertFalse(Member::idCardExists($first->id_card, $first->getKey()));
    }
}
