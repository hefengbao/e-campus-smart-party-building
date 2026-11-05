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
        // 三会一课会议台账：6 类会议，状态机 草稿→已提交→已归档/退回修改。
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')->constrained('organizations')->cascadeOnDelete()->comment('召开组织');
            $table->string('meeting_type', 32)->comment('会议类型：支部党员大会/支部委员会/党小组会/党课/主题党日/其他');
            $table->string('title', 255)->comment('会议标题');
            $table->timestamp('planned_at')->nullable()->comment('计划时间');
            $table->timestamp('actual_at')->nullable()->comment('实际召开时间');
            $table->string('location', 128)->nullable()->comment('地点');
            $table->string('host', 64)->nullable()->comment('主持人');
            $table->string('recorder', 64)->nullable()->comment('记录人');
            $table->string('status', 16)->default('草稿')->comment('状态：草稿/已提交/退回修改/已归档');
            $table->text('summary')->nullable()->comment('会议纪要');
            $table->json('key_points')->nullable()->comment('党员发言要点');
            $table->unsignedInteger('expected_count')->nullable()->comment('应到人数（按本支部正式党员自动统计，不允许前端修改）');
            $table->text('observers')->nullable()->comment('列席人员');
            $table->json('content_items')->nullable()->comment('会议主要内容 [{category, content}]');
            $table->timestamp('submitted_at')->nullable()->comment('提交时间');
            $table->string('submitted_by', 30)->nullable()->comment('提交人账号（users.id）');
            $table->timestamp('reviewed_at')->nullable()->comment('审核时间');
            $table->string('reviewed_by', 30)->nullable()->comment('审核人账号（users.id）');
            $table->text('review_comment')->nullable()->comment('审核意见（退回原因必填）');
            $table->string('created_by', 30)->nullable()->comment('创建人账号（users.id）');
            $table->timestamps();

            $table->index(['org_id', 'status']);
            $table->index('meeting_type');
        });

        // 参会人员考勤：确认与签到。
        Schema::create('meeting_attendees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('meetings')->cascadeOnDelete()->comment('所属会议');
            $table->string('member_id', 30)->comment('参会党员（members.id）');
            $table->string('confirm_status', 16)->default('待确认')->comment('确认状态：待确认/参加/请假');
            $table->string('attendance_status', 16)->default('未签到')->comment('考勤状态：出勤/迟到/请假/缺席/未签到');
            $table->timestamp('sign_in_at')->nullable()->comment('签到时间');
            $table->string('sign_in_method', 16)->nullable()->comment('签到方式：二维码/后台录入');
            $table->text('leave_reason')->nullable()->comment('请假原因');
            $table->timestamps();

            $table->unique(['meeting_id', 'member_id']);
        });

        // 会议材料归档：纪要 / 影像 / 签到表。
        Schema::create('meeting_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('meetings')->cascadeOnDelete()->comment('所属会议');
            $table->string('kind', 16)->default('其他')->comment('材料类型：纪要/影像/签到表/其他');
            $table->string('name', 255)->comment('材料名称');
            $table->string('file_path', 255)->nullable()->comment('存储路径');
            $table->string('uploaded_by', 30)->nullable()->comment('上传人账号（users.id）');
            $table->timestamps();
        });

        // 台账流转留痕：提交 / 退回 / 归档。
        Schema::create('meeting_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('meetings')->cascadeOnDelete()->comment('所属会议');
            $table->string('action', 32)->comment('动作：提交/退回修改/归档');
            $table->string('operator', 30)->nullable()->comment('操作人账号（users.id）');
            $table->string('role', 32)->nullable()->comment('操作角色：支部/院级');
            $table->text('comment')->nullable()->comment('备注');
            $table->timestamp('created_at')->nullable();
        });

        // 年度组织生活计划：用于"未按期开展"提醒。
        Schema::create('meeting_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')->constrained('organizations')->cascadeOnDelete()->comment('组织');
            $table->unsignedSmallInteger('year')->comment('年度');
            $table->unsignedTinyInteger('month')->comment('月份');
            $table->string('meeting_type', 32)->comment('会议类型');
            $table->unsignedInteger('planned_times')->default(1)->comment('计划开展次数');
            $table->text('note')->nullable()->comment('备注');
            $table->timestamps();

            $table->unique(['org_id', 'year', 'month', 'meeting_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_plans');
        Schema::dropIfExists('meeting_logs');
        Schema::dropIfExists('meeting_attachments');
        Schema::dropIfExists('meeting_attendees');
        Schema::dropIfExists('meetings');
    }
};
