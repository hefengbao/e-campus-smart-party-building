<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('member_organization', function (Blueprint $table) {
            $table->id();
            $table->string('member_id');
            $table->foreignId('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->string('status', 20)->default('正常')->comment('正常/已转出');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();

            $table->foreign('member_id')->references('id')->on('members')->cascadeOnDelete();
            $table->unique(['member_id', 'organization_id']);
            $table->index(['organization_id', 'status']);
        });

        DB::table('members')
            ->select(['id', 'org_id'])
            ->orderBy('id')
            ->chunkById(500, function ($members): void {
                $now = now();
                $links = $members->map(fn (object $member): array => [
                    'member_id' => $member->id,
                    'organization_id' => $member->org_id,
                    'is_primary' => true,
                    'status' => '正常',
                    'start_date' => null,
                    'end_date' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                DB::table('member_organization')->insert($links);
            }, 'id');

        Schema::table('members', function (Blueprint $table): void {
            $table->dropIndex(['org_id', 'status']);
            $table->dropConstrainedForeignId('org_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $hasNonPrimaryLinks = DB::table('member_organization')
            ->where('is_primary', false)
            ->orWhere('status', '!=', '正常')
            ->exists();

        $hasMembersWithoutPrimary = DB::table('members')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('member_organization')
                    ->whereColumn('member_organization.member_id', 'members.id')
                    ->where('member_organization.is_primary', true)
                    ->where('member_organization.status', '正常');
            })
            ->exists();

        if ($hasNonPrimaryLinks || $hasMembersWithoutPrimary) {
            throw new RuntimeException('Cannot roll back member organization links without losing non-primary or historical relationships.');
        }

        Schema::table('members', function (Blueprint $table): void {
            $table->foreignId('org_id')->constrained('organizations')->restrictOnDelete();
        });

        DB::table('member_organization')
            ->where('is_primary', true)
            ->orderBy('member_id')
            ->get(['member_id', 'organization_id'])
            ->each(function (object $link): void {
                DB::table('members')
                    ->where('id', $link->member_id)
                    ->update(['org_id' => $link->organization_id]);
            });

        Schema::table('members', function (Blueprint $table): void {
            $table->index(['org_id', 'status']);
        });

        Schema::dropIfExists('member_organization');
    }
};
