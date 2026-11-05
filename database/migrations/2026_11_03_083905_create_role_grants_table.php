<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('role_grants', function (Blueprint $table) {
            $table->id();
            $table->string('user_id', 30)->comment('账号（users.id，工号/学号）');
            $table->string('role_code', 30)->comment('super_admin/org_dept/college/training_coordinator/branch/member');
            $table->foreignId('org_id')->nullable()->constrained('organizations')->restrictOnDelete()->comment('数据范围组织');
            $table->boolean('is_primary')->default(false)->comment('主角色');
            $table->string('status', 20)->default('正常');
            $table->text('remark')->nullable();
            $table->string('granted_by', 30)->nullable()->comment('授予人账号（users.id）');
            $table->timestamps();

            $table->unique(['user_id', 'role_code', 'org_id']);
            $table->index('role_code');
        });

        Schema::create('phone_change_requests', function (Blueprint $table) {
            $table->id();
            $table->string('member_id', 30)->comment('党员（members.id，工号/学号）');
            $table->string('old_phone', 30)->nullable();
            $table->string('new_phone', 30);
            $table->string('reason', 200)->nullable();
            $table->string('status', 20)->default('待审核')->comment('待审核/已通过/已驳回');
            $table->text('review_note')->nullable();
            $table->string('reviewed_by', 30)->nullable()->comment('审核人账号（users.id）');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phone_change_requests');
        Schema::dropIfExists('role_grants');
    }
};
