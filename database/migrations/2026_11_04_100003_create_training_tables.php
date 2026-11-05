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
        // 干部培训学员名册：独立于党员库，党外干部可纳入；协同端无权查看名册。
        Schema::create('trainees', function (Blueprint $table) {
            $table->id();
            $table->string('staff_no', 64)->comment('工号');
            $table->string('name', 64)->comment('姓名');
            $table->string('gender', 8)->default('男')->comment('性别');
            $table->date('birth_date')->nullable()->comment('出生日期');
            $table->string('phone', 32)->nullable()->comment('联系电话');
            $table->foreignId('org_id')->nullable()->constrained('organizations')->nullOnDelete()->comment('所在单位组织');
            $table->string('org_name', 255)->nullable()->comment('单位名称（党外干部可不关联组织）');
            $table->string('department', 255)->nullable()->comment('所在部门');
            $table->string('position', 128)->nullable()->comment('职务');
            $table->string('cadre_level', 32)->nullable()->comment('干部层级：校领导/中层正职/中层副职/科级/一般干部');
            $table->string('cadre_type', 32)->default('党政管理干部')->comment('干部类型：党政管理干部/专业技术干部/党外干部/其他');
            $table->string('political_status', 32)->default('群众')->comment('政治面貌：中共党员/中共预备党员/民主党派/无党派人士/群众');
            $table->boolean('is_party_member')->default(false)->comment('是否党员');
            $table->string('member_id', 30)->nullable()->comment('关联党员（members.id，党员身份学员回填）');
            $table->string('status', 16)->default('在训')->comment('状态：在训/结业/调离/退休');
            $table->boolean('enabled')->default(true)->comment('是否启用');
            $table->string('data_source', 32)->nullable()->comment('数据来源：人事系统/组织部导入/手工录入');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamps();
            $table->softDeletes();

            $table->unique('staff_no');
            $table->index(['status', 'enabled']);
            $table->index('cadre_type');
        });

        // 课程资源。
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255)->comment('课程标题');
            $table->string('category', 32)->default('党课')->comment('分类：党课/党史教育/理论解读/业务能力/时政');
            $table->string('provider_level', 16)->default('校级')->comment('提供层级：校级/院级/支部');
            $table->foreignId('org_id')->nullable()->constrained('organizations')->nullOnDelete()->comment('提供组织');
            $table->decimal('hours', 6, 1)->default(0)->comment('学时');
            $table->string('resource_url', 500)->nullable()->comment('资源链接');
            $table->text('description')->nullable()->comment('课程简介');
            $table->json('tags')->nullable()->comment('标签');
            $table->boolean('publish')->default(true)->comment('是否发布');
            $table->unsignedInteger('view_count')->default(0)->comment('浏览次数');
            $table->string('created_by', 30)->nullable()->comment('创建人账号（users.id）');
            $table->timestamps();

            $table->index(['category', 'publish']);
        });

        // 培训项目：校级/院级/支部三级发布。
        Schema::create('trainings', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255)->comment('培训标题');
            $table->string('category', 32)->default('党员教育')->comment('分类：党员教育/干部培训/发展对象培训');
            $table->foreignId('org_id')->nullable()->constrained('organizations')->nullOnDelete()->comment('发布组织');
            $table->string('provider_level', 16)->default('校级')->comment('发布层级：校级/院级/支部');
            $table->decimal('hours_standard', 6, 1)->default(0)->comment('标准学时');
            $table->json('target_scope')->nullable()->comment('面向范围 {identities:[],orgIds:[],memberCategories:[]}');
            $table->date('start_date')->nullable()->comment('开始日期');
            $table->date('end_date')->nullable()->comment('结束日期');
            $table->date('signup_deadline')->nullable()->comment('报名截止');
            $table->boolean('need_signup')->default(true)->comment('是否需报名');
            $table->boolean('need_approval')->default(true)->comment('是否需审批');
            $table->string('status', 16)->default('报名中')->comment('状态：草稿/报名中/进行中/已结束');
            $table->string('checkin_code', 16)->nullable()->comment('动态签到码');
            $table->timestamp('checkin_code_expires_at')->nullable()->comment('签到码过期时间');
            $table->text('description')->nullable()->comment('培训说明');
            $table->string('created_by', 30)->nullable()->comment('创建人账号（users.id）');
            $table->timestamps();

            $table->index(['org_id', 'status']);
            $table->index('provider_level');
        });

        // 培训报名与逐级审批（支部→院级）。
        Schema::create('training_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_id')->constrained('trainings')->cascadeOnDelete()->comment('培训项目');
            $table->string('member_id', 30)->comment('报名党员（members.id）');
            $table->string('status', 16)->default('待支部审核')->comment('状态：待支部审核/待院级审核/已通过/已驳回');
            $table->timestamp('applied_at')->nullable()->comment('报名时间');
            $table->string('branch_reviewer', 30)->nullable()->comment('支部审核人（users.id）');
            $table->timestamp('branch_reviewed_at')->nullable()->comment('支部审核时间');
            $table->string('college_reviewer', 30)->nullable()->comment('院级审核人（users.id）');
            $table->timestamp('college_reviewed_at')->nullable()->comment('院级审核时间');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamps();

            $table->unique(['training_id', 'member_id']);
            $table->index('status');
        });

        // 培训签到（动态二维码 / 后台补录）。
        Schema::create('training_checkins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_id')->constrained('trainings')->cascadeOnDelete()->comment('培训项目');
            $table->string('member_id', 30)->comment('签到党员（members.id）');
            $table->timestamp('checkin_at')->nullable()->comment('签到时间');
            $table->string('method', 16)->nullable()->comment('方式：二维码/后台录入');
            $table->string('status', 16)->default('出勤')->comment('状态：出勤/迟到/请假/缺席');
            $table->string('operator', 30)->nullable()->comment('操作人账号（users.id）');
            $table->timestamps();

            $table->unique(['training_id', 'member_id']);
        });

        // 学时流水：组织部直录已生效；协同端提交待审核。
        Schema::create('training_hours', function (Blueprint $table) {
            $table->id();
            $table->string('member_id', 30)->nullable()->comment('关联党员（members.id）');
            $table->foreignId('trainee_id')->nullable()->constrained('trainees')->nullOnDelete()->comment('关联学员（优先归属主体）');
            $table->foreignId('org_id')->nullable()->constrained('organizations')->nullOnDelete()->comment('学员所在组织');
            $table->string('source_type', 16)->default('线下培训')->comment('来源：线下培训/线上课程/批量导入');
            $table->unsignedBigInteger('source_id')->nullable()->comment('来源记录 ID');
            $table->string('title', 255)->comment('学时名称');
            $table->string('project_name', 255)->nullable()->comment('所属培训项目');
            $table->string('training_category', 16)->nullable()->comment('口径：网络自学/集中培训');
            $table->string('training_form', 32)->nullable()->comment('形式：学校学习平台/其他网络平台/脱产培训等');
            $table->decimal('hours', 6, 1)->default(0)->comment('学时数');
            $table->date('occurred_on')->nullable()->comment('发生日期');
            $table->unsignedSmallInteger('year')->comment('年度');
            $table->string('status', 16)->default('已生效')->comment('状态：已生效/待审核/已驳回');
            $table->foreignId('submitter_org_id')->nullable()->constrained('organizations')->nullOnDelete()->comment('提交方组织（协同端）');
            $table->string('submitted_by', 30)->nullable()->comment('提交人账号（users.id）');
            $table->string('reviewer', 30)->nullable()->comment('审核人账号（users.id）');
            $table->timestamp('reviewed_at')->nullable()->comment('审核时间');
            $table->text('review_note')->nullable()->comment('审核意见');
            $table->text('remark')->nullable()->comment('备注');
            $table->string('created_by', 30)->nullable()->comment('创建人账号（users.id）');
            $table->timestamps();

            $table->index(['trainee_id', 'year', 'status']);
            $table->index(['member_id', 'year', 'status']);
            $table->index('status');
        });

        // 年度学时标准（按身份/类别）。
        Schema::create('hour_standards', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->comment('年度');
            $table->string('target_key', 64)->comment('对象：默认/教职工党员/学生党员/干部');
            $table->decimal('required_hours', 6, 1)->default(32)->comment('应修学时');
            $table->text('note')->nullable()->comment('说明');
            $table->timestamps();

            $table->unique(['year', 'target_key']);
        });

        // 组织关系转接：转出/转入，三级审批（支部→院级→组织部）。
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->string('member_id', 30)->comment('转接党员（members.id）');
            $table->string('direction', 8)->default('转出')->comment('方向：转出/转入');
            $table->string('transfer_type', 16)->default('校外')->comment('类型：校内/省内/跨省/校外');
            $table->foreignId('from_org_id')->nullable()->constrained('organizations')->nullOnDelete()->comment('转出组织');
            $table->string('to_org_name', 255)->comment('去向组织名称');
            $table->string('to_org_code', 64)->nullable()->comment('去向组织编码');
            $table->text('reason')->nullable()->comment('转接原因');
            $table->timestamp('apply_at')->nullable()->comment('申请时间');
            $table->string('status', 16)->default('待支部审核')->comment('状态：待支部审核/待院级审核/待组织部审核/已办结/已驳回');
            $table->string('branch_reviewer', 30)->nullable()->comment('支部审核人（users.id）');
            $table->timestamp('branch_reviewed_at')->nullable()->comment('支部审核时间');
            $table->string('college_reviewer', 30)->nullable()->comment('院级审核人（users.id）');
            $table->timestamp('college_reviewed_at')->nullable()->comment('院级审核时间');
            $table->string('dept_reviewer', 30)->nullable()->comment('组织部审核人（users.id）');
            $table->timestamp('dept_reviewed_at')->nullable()->comment('组织部审核时间');
            $table->string('letter_no', 64)->nullable()->comment('介绍信编号（办结生成）');
            $table->timestamp('finished_at')->nullable()->comment('办结时间');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamps();

            $table->index(['member_id', 'status']);
            $table->index('status');
        });

        // 因私出国（境）审批：按天数自动分流（<6 个月 / ≥6 个月）。
        Schema::create('abroad_applications', function (Blueprint $table) {
            $table->id();
            $table->string('member_id', 30)->comment('申请党员（members.id）');
            $table->string('destination', 128)->comment('目的地');
            $table->text('reason')->nullable()->comment('事由');
            $table->date('start_date')->comment('出发日期');
            $table->date('end_date')->comment('返回日期');
            $table->unsignedInteger('duration_days')->default(0)->comment('天数（自动计算）');
            $table->string('approval_route', 16)->default('6个月以下')->comment('审批路径：6个月以下/6个月及以上');
            $table->boolean('retain_relation')->default(true)->comment('是否保留组织关系');
            $table->string('contact_phone', 32)->nullable()->comment('境外联系方式');
            $table->string('status', 16)->default('待支部审核')->comment('状态：待支部审核/待院级审核/待组织部审核/已批准/已驳回/已销假');
            $table->string('branch_reviewer', 30)->nullable()->comment('支部审核人（users.id）');
            $table->timestamp('branch_reviewed_at')->nullable()->comment('支部审核时间');
            $table->string('college_reviewer', 30)->nullable()->comment('院级审核人（users.id）');
            $table->timestamp('college_reviewed_at')->nullable()->comment('院级审核时间');
            $table->string('dept_reviewer', 30)->nullable()->comment('组织部审核人（users.id）');
            $table->timestamp('dept_reviewed_at')->nullable()->comment('组织部审核时间');
            $table->date('return_date')->nullable()->comment('实际回国日期');
            $table->text('cancel_note')->nullable()->comment('销假说明');
            $table->timestamps();

            $table->index(['member_id', 'status']);
            $table->index('status');
        });

        // 延长保留组织关系期限申请。
        Schema::create('abroad_extensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('abroad_application_id')->constrained('abroad_applications')->cascadeOnDelete()->comment('所属申请');
            $table->unsignedTinyInteger('extend_months')->default(6)->comment('延长月数');
            $table->text('reason')->nullable()->comment('延期原因');
            $table->string('status', 16)->default('待审批')->comment('状态：待审批/已批准/已驳回');
            $table->timestamp('applied_at')->nullable()->comment('申请时间');
            $table->timestamp('approved_at')->nullable()->comment('审批时间');
            $table->string('approver', 30)->nullable()->comment('审批人账号（users.id）');
            $table->timestamps();
        });

        // 党组织与出国（境）党员定期联系记录。
        Schema::create('abroad_contact_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('abroad_application_id')->constrained('abroad_applications')->cascadeOnDelete()->comment('所属申请');
            $table->date('contact_date')->comment('联系日期');
            $table->string('method', 16)->default('电话')->comment('方式：电话/邮件/视频/其他');
            $table->text('content')->nullable()->comment('联系内容');
            $table->string('operator', 30)->nullable()->comment('操作人账号（users.id）');
            $table->timestamp('created_at')->nullable()->comment('创建时间');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('abroad_contact_logs');
        Schema::dropIfExists('abroad_extensions');
        Schema::dropIfExists('abroad_applications');
        Schema::dropIfExists('transfers');
        Schema::dropIfExists('hour_standards');
        Schema::dropIfExists('training_hours');
        Schema::dropIfExists('training_checkins');
        Schema::dropIfExists('training_enrollments');
        Schema::dropIfExists('trainings');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('trainees');
    }
};
