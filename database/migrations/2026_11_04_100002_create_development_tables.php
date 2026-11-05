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
        // 五阶段 25 步骤定义（流程配置表，种子数据预置）。
        Schema::create('development_step_definitions', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('stage_no')->comment('阶段号 1-5');
            $table->string('stage_name', 64)->comment('阶段名称');
            $table->unsignedSmallInteger('step_no')->unique()->comment('步骤号 1-25（全局唯一）');
            $table->string('step_name', 128)->comment('步骤名称');
            $table->unsignedInteger('default_days')->default(30)->comment('默认办理时限（天），0 表示无时限');
            $table->string('responsible_role', 32)->default('branch')->comment('办理主体：member/branch/college/org_dept');
            $table->json('required_materials')->nullable()->comment('必放材料清单');
            $table->text('description')->nullable()->comment('说明');
            $table->timestamps();

            $table->index('stage_no');
        });

        // 党员发展流程实例（一人一案）。
        Schema::create('development_cases', function (Blueprint $table) {
            $table->id();
            $table->string('member_id', 30)->comment('发展对象党员档案（members.id）');
            $table->foreignId('org_id')->nullable()->constrained('organizations')->nullOnDelete()->comment('所属组织');
            $table->unsignedTinyInteger('stage_no')->default(1)->comment('当前阶段 1-5');
            $table->unsignedSmallInteger('step_no')->default(1)->comment('当前步骤 1-25');
            $table->string('status', 16)->default('进行中')->comment('状态：进行中/已完成/已终止');
            $table->date('apply_date')->nullable()->comment('递交入党申请书日期');
            $table->timestamp('started_at')->nullable()->comment('建档时间');
            $table->timestamp('finished_at')->nullable()->comment('完成/终止时间');
            $table->text('remark')->nullable()->comment('备注（终止原因等）');
            $table->timestamps();

            $table->unique('member_id');
            $table->index(['org_id', 'status']);
        });

        // 发展流程步骤记录（材料、时限、审批留痕）。
        Schema::create('development_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('development_cases')->cascadeOnDelete()->comment('所属案件');
            $table->unsignedTinyInteger('stage_no')->comment('阶段号');
            $table->unsignedSmallInteger('step_no')->comment('步骤号');
            $table->string('step_name', 128)->comment('步骤名称（冗余自定义表）');
            $table->string('status', 16)->default('未开始')->comment('状态：未开始/进行中/已完成/逾期');
            $table->date('planned_date')->nullable()->comment('计划完成日期');
            $table->date('done_date')->nullable()->comment('实际完成日期');
            $table->string('operator', 30)->nullable()->comment('经办人账号（users.id）');
            $table->text('opinion')->nullable()->comment('审批意见');
            $table->json('materials')->nullable()->comment('材料 [{name, path, uploadedAt}]');
            $table->json('detail')->nullable()->comment('分步骤结构化表单数据');
            $table->timestamps();

            $table->unique(['case_id', 'step_no']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('development_steps');
        Schema::dropIfExists('development_cases');
        Schema::dropIfExists('development_step_definitions');
    }
};
