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
        // 党务工作坊活动：公开活动先审后发，不公开活动保存即发布。
        // status 只存流程状态（草稿/待审核/已驳回/已发布），展示状态由审核状态+时间推演。
        Schema::create('workshop_activities', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255)->comment('活动标题');
            $table->string('category', 32)->default('党务培训')->comment('分类：党务培训/经验交流/技能竞赛/其他');
            $table->foreignId('org_id')->nullable()->constrained('organizations')->nullOnDelete()->comment('发布组织');
            $table->text('description')->nullable()->comment('活动说明');
            $table->string('location', 255)->nullable()->comment('活动地点');
            $table->timestamp('start_at')->nullable()->comment('活动开始时间');
            $table->timestamp('end_at')->nullable()->comment('活动结束时间');
            $table->timestamp('enroll_deadline')->nullable()->comment('报名截止时间');
            $table->boolean('is_public')->default(true)->comment('是否公开（公开需审核，不公开保存即发布）');
            $table->boolean('cross_college')->default(true)->comment('是否允许跨院报名');
            $table->integer('max_enrollments')->default(0)->comment('报名上限（0=不限）');
            $table->string('status', 16)->default('草稿')->comment('流程状态：草稿/待审核/已驳回/已发布');
            $table->text('reject_reason')->nullable()->comment('驳回原因');
            $table->string('created_by', 30)->nullable()->comment('发布人账号（users.id）');
            $table->string('reviewed_by', 30)->nullable()->comment('审核人账号（users.id）');
            $table->timestamp('reviewed_at')->nullable()->comment('审核时间');
            $table->timestamp('published_at')->nullable()->comment('发布时间');
            $table->text('summary')->nullable()->comment('活动总结（结束后 7 日内归档）');
            $table->timestamp('archived_at')->nullable()->comment('总结归档时间');
            $table->timestamps();

            $table->index(['org_id', 'status']);
            $table->index('start_at');
        });

        // 活动报名：单位名义报名，跨院报名需发布方（或组织部）审核。
        Schema::create('workshop_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('workshop_activities')->cascadeOnDelete()->comment('活动');
            $table->foreignId('org_id')->constrained('organizations')->cascadeOnDelete()->comment('报名单位组织');
            $table->string('contact_user', 30)->nullable()->comment('联系人账号（users.id）');
            $table->integer('headcount')->default(1)->comment('报名人数');
            $table->string('status', 16)->default('待审核')->comment('状态：待审核/已通过/已驳回');
            $table->text('remark')->nullable()->comment('报名说明');
            $table->string('reviewed_by', 30)->nullable()->comment('审核人账号（users.id）');
            $table->timestamp('reviewed_at')->nullable()->comment('审核时间');
            $table->timestamps();

            $table->unique(['activity_id', 'org_id']);
            $table->index('status');
        });

        // 党务工作坊链接导航：分类、置顶、排序、点击计数。
        Schema::create('workshop_links', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255)->comment('链接标题');
            $table->string('url', 500)->comment('链接地址');
            $table->string('category', 32)->default('常用工具')->comment('分类：常用工具/学习资料/办事指南/其他');
            $table->unsignedInteger('sort')->default(0)->comment('排序（小在前）');
            $table->boolean('is_pinned')->default(false)->comment('是否置顶');
            $table->unsignedInteger('click_count')->default(0)->comment('点击次数');
            $table->boolean('enabled')->default(true)->comment('是否启用');
            $table->string('created_by', 30)->nullable()->comment('创建人账号（users.id）');
            $table->timestamps();

            $table->index(['category', 'enabled']);
        });

        // 工作坊问答帖：标签必填单选，敏感词过滤。
        Schema::create('workshop_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255)->comment('帖子标题');
            $table->text('content')->comment('帖子正文');
            $table->string('tag', 32)->comment('标签（必填单选）：党员发展/组织关系/教育培训/换届选举/党费管理/其他');
            $table->string('author', 30)->comment('发帖人账号（users.id）');
            $table->string('author_name', 64)->nullable()->comment('发帖人姓名快照');
            $table->boolean('is_pinned')->default(false)->comment('是否置顶');
            $table->boolean('is_hidden')->default(false)->comment('是否隐藏');
            $table->boolean('is_resolved')->default(false)->comment('楼主是否已标记解决');
            $table->unsignedInteger('view_count')->default(0)->comment('浏览次数');
            $table->timestamps();

            $table->index(['tag', 'is_hidden']);
        });

        // 问答回帖：组织部回帖自动标记官方回复；楼主可标记最佳答案。
        Schema::create('workshop_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('workshop_posts')->cascadeOnDelete()->comment('所属帖子');
            $table->text('content')->comment('回帖内容');
            $table->string('author', 30)->comment('回帖人账号（users.id）');
            $table->string('author_name', 64)->nullable()->comment('回帖人姓名快照');
            $table->boolean('is_official')->default(false)->comment('是否官方回复（组织部）');
            $table->boolean('is_best')->default(false)->comment('是否最佳答案');
            $table->boolean('is_hidden')->default(false)->comment('是否隐藏');
            $table->timestamps();

            $table->index(['post_id', 'is_hidden']);
        });

        // 站内消息（Laravel database channel）。
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index('read_at');
        });

        // 监督预警：9 类规则扫描生成，dedup_key 幂等，三级（提示/警告/严重）。
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->string('rule', 32)->comment('规则：换届到期预警/组织生活提醒/发展流程超期/发展材料缺失/思想汇报逾期/发展条件不符/预备期到期/学时未达标/党建任务督办');
            $table->string('level', 8)->default('提示')->comment('级别：提示/警告/严重');
            $table->string('title', 255)->comment('预警标题');
            $table->text('content')->nullable()->comment('预警内容');
            $table->foreignId('org_id')->nullable()->constrained('organizations')->nullOnDelete()->comment('所属组织');
            $table->string('member_id', 30)->nullable()->comment('关联党员（members.id）');
            $table->string('biz_type', 32)->nullable()->comment('业务类型：organization/development_case/training_hour/supervision_task');
            $table->unsignedBigInteger('biz_id')->nullable()->comment('业务主键');
            $table->string('dedup_key', 191)->unique()->comment('幂等键（重复扫描只刷新不新增）');
            $table->string('status', 16)->default('未处置')->comment('状态：未处置/已处置/已忽略');
            $table->string('handled_by', 30)->nullable()->comment('处置人账号（users.id）');
            $table->timestamp('handled_at')->nullable()->comment('处置时间');
            $table->text('handle_note')->nullable()->comment('处置说明');
            $table->timestamp('last_scanned_at')->nullable()->comment('最近扫描时间');
            $table->timestamps();

            $table->index(['rule', 'status']);
            $table->index(['org_id', 'status']);
        });

        // 党建任务督办。
        Schema::create('supervision_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255)->comment('任务标题');
            $table->text('content')->nullable()->comment('任务内容');
            $table->foreignId('org_id')->nullable()->constrained('organizations')->nullOnDelete()->comment('责任组织');
            $table->date('due_date')->comment('截止日期');
            $table->string('status', 16)->default('进行中')->comment('状态：进行中/已完成/已逾期');
            $table->string('assignee', 30)->nullable()->comment('责任人账号（users.id）');
            $table->timestamp('completed_at')->nullable()->comment('完成时间');
            $table->text('completion_note')->nullable()->comment('完成说明');
            $table->string('created_by', 30)->nullable()->comment('创建人账号（users.id）');
            $table->timestamps();

            $table->index(['org_id', 'status']);
        });

        // 政治生日推送台账：未来 7 天内入党周年党员，幂等（member+birthday_date+channel）。
        Schema::create('birthday_pushes', function (Blueprint $table) {
            $table->id();
            $table->string('member_id', 30)->comment('党员（members.id）');
            $table->date('birthday_date')->comment('政治生日日期（入党周年日）');
            $table->integer('years')->default(1)->comment('第 N 个政治生日');
            $table->string('channel', 16)->default('站内')->comment('推送渠道：站内/门户');
            $table->string('content', 500)->comment('推送内容');
            $table->string('status', 16)->default('待推送')->comment('状态：待推送/已推送/失败');
            $table->timestamp('pushed_at')->nullable()->comment('推送时间');
            $table->timestamps();

            $table->unique(['member_id', 'birthday_date', 'channel']);
            $table->index('status');
        });

        // 党费记录：只读对接，fee/sync 端点接收推送，按工号/身份证匹配。
        Schema::create('fee_records', function (Blueprint $table) {
            $table->id();
            $table->string('member_id', 30)->comment('党员（members.id）');
            $table->string('period', 7)->comment('所属月份（YYYY-MM）');
            $table->decimal('amount', 10, 2)->default(0)->comment('党费金额');
            $table->string('pay_status', 16)->default('已交纳')->comment('交纳状态：已交纳/未交纳');
            $table->date('pay_date')->nullable()->comment('交纳日期');
            $table->string('source', 32)->default('党费系统同步')->comment('数据来源');
            $table->timestamps();

            $table->unique(['member_id', 'period']);
            $table->index('period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_records');
        Schema::dropIfExists('birthday_pushes');
        Schema::dropIfExists('supervision_tasks');
        Schema::dropIfExists('alerts');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('workshop_replies');
        Schema::dropIfExists('workshop_posts');
        Schema::dropIfExists('workshop_links');
        Schema::dropIfExists('workshop_enrollments');
        Schema::dropIfExists('workshop_activities');
    }
};
