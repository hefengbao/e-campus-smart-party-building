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
        // 委员会成员表：党支部委员会 / 党委（二级）委员会共用，
        // 通过 organization.level 区分（3 = 党支部委员会，2 = 党委委员会）。
        Schema::create('committee_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete()->comment('所在组织（支部或院级党委）');
            $table->string('member_id', 30)->comment('委员对应的党员档案（members.id）');
            $table->string('position', 50)->comment('职务：党支部书记/党支部副书记/组织委员/宣传委员/党委书记等，可配置');
            $table->string('appointment_method', 20)->default('选举')->comment('任命方式：选举/指派');
            $table->date('term_start')->comment('任期开始');
            $table->date('term_end')->nullable()->comment('任期结束，为空表示本届在任');
            $table->string('status', 20)->default('在任')->comment('在任状态：在任/离任');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index('member_id');
        });

        // 组织部工作人员：控制 org panel 进入权限。
        Schema::create('org_staff', function (Blueprint $table) {
            $table->id();
            $table->string('user_id', 30)->comment('关联账号（users.id）');
            $table->string('name', 50)->comment('姓名');
            $table->string('position', 50)->nullable()->comment('职务');
            $table->string('phone', 30)->nullable();
            $table->string('status', 20)->default('在职')->comment('在职状态：在职/离职');
            $table->timestamps();

            $table->unique('user_id');
        });

        // 系统管理员：控制 sysadmin panel 进入权限。
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('user_id', 30)->comment('关联账号（users.id）');
            $table->string('name', 50)->comment('姓名');
            $table->string('status', 20)->default('启用')->comment('状态：启用/停用');
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admins');
        Schema::dropIfExists('org_staff');
        Schema::dropIfExists('committee_members');
    }
};
